import boto3
from botocore.exceptions import ClientError

s3 = boto3.client("s3")

def _list_keys_with_prefix(bucket, prefix):
    paginator = s3.get_paginator("list_objects_v2")
    for page in paginator.paginate(Bucket=bucket, Prefix=prefix):
        for obj in page.get("Contents", []) if page.get("Contents") else []:
            yield obj["Key"]

def _delete_keys(bucket, keys):
    """Delete keys in batches up to 1000 and return dict with Deleted and Errors lists."""
    deleted = []
    errors = []
    for i in range(0, len(keys), 1000):
        chunk = keys[i : i + 1000]
        try:
            resp = s3.delete_objects(Bucket=bucket, Delete={"Objects": [{"Key": k} for k in chunk]})
        except ClientError as e:
            # fatal API error for this chunk
            print("ClientError deleting chunk:", e)
            errors.append({"Message": str(e), "Keys": chunk})
            continue

        for d in resp.get("Deleted", []):
            deleted.append(d.get("Key"))
        for err in resp.get("Errors", []):
            errors.append(err)
    return {"Deleted": deleted, "Errors": errors}

def plan_for_duplicate_deletion(bucket, prefix):
    candidates = sorted([(k.rsplit("/", 1)[-1], k)
                          for k in _list_keys_with_prefix(bucket, prefix)],
                        key=lambda x: x[0], reverse=True)

    if not candidates:
        return {"info": "no candidates found", "prefix": prefix}

    latest_object = candidates.pop(0)[1]

    return {
        "bucket": bucket,
        "prefix": prefix,
        "latest_object": latest_object,
        "to_delete": [c[1] for c in candidates]
    }

def lambda_handler(event, context=None):
    results = []
    unduplicated_results = set()
    for rec in event.get("Records", []):
        s3info = rec.get("s3", {})
        bucket = s3info.get("bucket", {}).get("name")
        key = s3info.get("object", {}).get("key")
        if not bucket or not key:
            results.append({"error": "missing bucket/key", "record": rec})
            continue
        key_seg = key.split("_c")[0] + "_c"
        unduplicated_results.add((bucket, key_seg))

    for bucket, key_seg in unduplicated_results:
        plan = plan_for_duplicate_deletion(bucket, key_seg)
        print("Plan for prefix:", key_seg)

        # If there are keys to delete, perform deletion
        to_delete = plan["to_delete"]
        if not to_delete:
            print("No older objects to delete.")
            results.append({**plan, "delete_result": {"Deleted": [], "Errors": []}})
            continue

        delete_resp = _delete_keys(bucket, to_delete)
        print("Delete response:", delete_resp)
        results.append({**plan, "delete_result": delete_resp})

    return {"status": "ok", "results": results}
