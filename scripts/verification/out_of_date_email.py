#!/usr/bin/env python3
import os
import sys
import yaml
import s3fs
import smtplib
from datetime import datetime, timedelta, timezone
from email.message import EmailMessage

# 1) CONFIGURATION
BUCKET     = os.environ.get("HFRTV_BUCKET", "rps-nccf-hfrnet-dissemination-uat")
PREFIX     = os.environ.get("HFRTV_PREFIX", "hfrtv")
THRESH_MIN = int(os.environ.get("HFRTV_THRESHOLD_MIN", "90"))

SMTP_HOST  = os.environ.get("SMTP_HOST", "email-smtp.us-east-1.amazonaws.com")
# expects implicit SSL - on AWS SES this is port 465 or 2465
SMTP_PORT  = int(os.environ.get("SMTP_PORT", 465))
SMTP_USER  = os.environ["SMTP_USER"]
SMTP_PASS  = os.environ["SMTP_PASS"]

EMAIL_FROM = os.environ.get("EMAIL_FROM", "admin@ioos.us")
EMAIL_TO   = os.environ.get("EMAIL_TO", "nccf_pg_monitoring@noaa.gov, brian.zelenke@noaa.gov")

# 2) station config
STATION_CFG = """
stations:
  akns:
    resolutions: [6km]
    active: false

  gak:
    resolutions: [1km,2km,6km]
    active: false

  glna:
    resolutions: [500m,1km,2km,6km]
    active: false

  prvi:
    resolutions: [2km,6km]
    active: true

  usegc:
    resolutions: [1km,2km,6km]
    active: true

  ushi:
    resolutions: [1km,2km,6km]
    active: true

  uswc:
    resolutions: [500m,1km,2km,6km]
    active: true
"""
cfg = yaml.safe_load(STATION_CFG)["stations"]

THRESHOLD = timedelta(minutes=THRESH_MIN)

# 3) helpers
def date_prefixes(dt):
    return dt.strftime("%Y/%m/%d")

def _to_dt(lm):
    if lm is None:
        return None
    if isinstance(lm, str):
        try:
            lm = datetime.fromisoformat(lm)
        except Exception:
            pass
    if isinstance(lm, datetime):
        if lm.tzinfo is None:
            lm = lm.replace(tzinfo=timezone.utc)
        return lm
    return None

def find_latest(fs, pattern):
    matches = fs.glob(pattern)
    if not matches:
        return None, None

    newest = (None, datetime.min.replace(tzinfo=timezone.utc))
    for path in matches:
        info = fs.info(path)
        lm_raw   = info.get("LastModified") or info.get("last_modified") or info.get("LastModifiedDate")
        lm = _to_dt(lm_raw)
        if lm is None:
            continue
        if lm > newest[1]:
            newest = (path, lm)
    return newest

def list_recent_files(fs, pattern, since_dt):
    matches = fs.glob(pattern)
    recs = []
    for path in matches:
        info = fs.info(path)
        lm_raw = info.get("LastModified") or info.get("last_modified") or info.get("LastModifiedDate")
        lm = _to_dt(lm_raw)
        if lm is None:
            continue
        if lm >= since_dt:
            recs.append((path, lm))
    recs.sort(key=lambda x: x[1], reverse=True)
    return recs

def human_delta(td):
    total_seconds = int(td.total_seconds())
    if total_seconds < 0:
        total_seconds = 0
    days, rem = divmod(total_seconds, 86400)
    hours, rem = divmod(rem, 3600)
    minutes, seconds = divmod(rem, 60)
    parts = []
    if days:
        parts.append(f"{days}d")
    if hours:
        parts.append(f"{hours}h")
    if minutes:
        parts.append(f"{minutes}m")
    if not parts:
        parts.append(f"{seconds}s")
    return "".join(parts)

# 4) main
def main():
    fs = s3fs.S3FileSystem(anon=False)
    now = datetime.now(timezone.utc)
    days_to_try = [now, now - timedelta(days=1)]
    stale = []
    details_map = {}

    for station, details in cfg.items():
        if not details["active"]:
            continue
        for res in details["resolutions"]:
            for kind in ("uwls", "25h-avg"):  # "25h-avg" is the 25-hour average product
                # filenames: hourly products are "uwls"; 25-hour averages are "25h-avg" under averages/
                wildcard = (
                    f"rtv-{station}-{res}-uwls_v1r0_hfr_*.nc"
                    if kind == "uwls"
                    else f"rtv-{station}-{res}-25h-avg_v1r0_hfr_*.nc"
                )
                subdir = "" if kind == "uwls" else "averages"

                latest_path = latest_lm = None
                for dt in days_to_try:
                    prefix = date_prefixes(dt)
                    pattern = f"{BUCKET}/{PREFIX}/{prefix}/"
                    if subdir:
                        pattern += f"{subdir}/"
                    pattern += wildcard

                    path, lm = find_latest(fs, "s3://"+pattern)
                    if path:
                        latest_path, latest_lm = path, lm
                        break

                key = f"{station} {res} {kind}"
                details_map[key] = {
                    "station": station,
                    "res": res,
                    "kind": kind,
                    "latest_path": latest_path,
                    "latest_lm": latest_lm,
                }

                if not latest_path:
                    stale.append((station, res, kind, "NO FILE"))
                    continue

                age = now - latest_lm
                details_map[key]["age"] = age
                details_map[key]["age_human"] = human_delta(age)
                details_map[key]["latest_lm_iso"] = latest_lm.isoformat()

                if age > THRESHOLD:
                    stale.append((station, res, kind, f"last updated {latest_lm.isoformat()} ({human_delta(age)} ago)"))

                since_24h = now - timedelta(hours=24)

                recent_matches = []
                for dt in [now, now - timedelta(days=1), now - timedelta(days=2)]:
                    prefix = date_prefixes(dt)
                    pattern = f"s3://{BUCKET}/{PREFIX}/{prefix}/"
                    if subdir:
                        pattern += f"{subdir}/"
                    pattern += wildcard
                    recs = list_recent_files(fs, pattern, since_24h)
                    recent_matches.extend(recs)

                seen = set()
                merged = []
                for p, lm in recent_matches:
                    if p in seen:
                        continue
                    seen.add(p)
                    merged.append((p, lm))
                merged.sort(key=lambda x: x[1], reverse=True)

                count_24h = sum(1 for p, lm in merged if lm >= since_24h)
                recent_list = [(os.path.basename(p), lm.isoformat()) for p, lm in merged[:5]]

                details_map[key].update({
                    "count_24h": count_24h,
                    "recent_files": recent_list,
                })

    # 5) send NCCF-style alert if any stale/missing products found
    if stale:
        msg = EmailMessage()
        subject = f"[ALERT] HFRadar products stale (> {THRESH_MIN}m) — Tetra Tech bucket {BUCKET}"
        msg["Subject"] = subject
        msg["From"]    = EMAIL_FROM
        msg["To"]      = EMAIL_TO

        lines = []
        lines.append(f"ALERT: HFRadar products appear stale in the Tetra Tech bucket: {BUCKET}")
        lines.append("")
        lines.append(f"Report generated: {now.isoformat()}")
        lines.append(f"Stale threshold: {THRESH_MIN} minutes (files older than this are flagged)")
        lines.append("")
        lines.append("Summary of missing/stale products:")
        for rec in stale:
            station, res, kind, detail = rec
            key = f"{station} {res} {kind}"
            d = details_map.get(key, {})
            latest_info = d.get("latest_lm_iso", "N/A")
            age_human = d.get("age_human", "N/A")
            # Make clear in text that 25h-avg represents a 25-hour average product
            kind_label = "Hourly (uwls)" if kind == "uwls" else "25-hour average (25h-avg)"
            lines.append(f"- {station} {res} {kind_label}: {detail}; last file: {latest_info}; age: {age_human}")

        lines.append("")
        lines.append("Detailed per-product information (includes successful-file context):")
        for key, d in sorted(details_map.items()):
            station = d["station"]
            res = d["res"]
            kind = d["kind"]
            kind_label = "Hourly (uwls)" if kind == "uwls" else "25-hour average (25h-avg)"
            lines.append(f"\nProduct: {key} -- {kind_label}")
            latest = d.get("latest_lm_iso") or "NO FILE FOUND"
            lines.append(f"  Latest file: {latest}")
            age = d.get("age_human", "N/A")
            lines.append(f"  Age: {age}")
            # explicit labels for the counts
            lines.append(f"  Count (number of files in last 24 hours): {d.get('count_24h', 0)}")
            recent = d.get("recent_files", [])
            if recent:
                lines.append("  Recent files (up to 5):")
                for fn, ts in recent:
                    lines.append(f"    - {fn} @ {ts}")
            else:
                lines.append("  Recent files: none found in the last 24 hours")

        lines.append("")
        lines.append("Notes:")
        lines.append("- 'Hourly (uwls)' are the hourly HFRNet UWLS files.")
        lines.append("- '25-hour average (25h-avg)' are 25-hour averages (stored under the 'averages/' subdirectory).")
        lines.append("- This email was generated automatically only because at least one product is stale or missing.")
        lines.append("- For troubleshooting: verify upstream processing and bucket prefixes/permissions.")

        msg.set_content("\n".join(lines))

        with smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT, timeout=10) as s:
            s.login(SMTP_USER, SMTP_PASS)
            s.send_message(msg)

        print("ALERT SENT. Summary lines:")
        for l in lines[:20]:
            print(l)
        sys.exit(1)
    else:
        print("All datasets are up to date. Sample counts:")
        now = datetime.now(timezone.utc)
        for key, d in sorted(details_map.items()):
            kind = d.get("kind")
            kind_label = "Hourly (uwls)" if kind == "uwls" else "25-hour average (25h-avg)"
            print(f"- {key} -- {kind_label}: Latest file: {d.get('latest_lm_iso', 'N/A')}, "
                  f"  Count (number of files in last 24 hours): {d.get('count_24h', 0)}")
        sys.exit(0)

if __name__ == "__main__":
    main()
