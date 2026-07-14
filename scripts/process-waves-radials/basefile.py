import datetime
import logging
import re

logger = logging.getLogger(__name__)


def is_number(n):
    try:
        float(n)
    except ValueError:
        return False
    return True


class BaseFile:
    """Base file class containing shared methods between both radial and wave files"""

    # SQL columns constants
    hardwareDiagnosticsSQLColumns = {
        "TMP": "receiver_chassis_tmp",
        "MTMP": "awg_tmp",
        "XTRP": "transmit_trip",
        "RUNT": "awg_run_time",
        "SP24": "receiver_supply_p24vdc",
        "SP05": "receiver_supply_p5vdc",
        "SN05": "receiver_supply_n5vdc",
        "SP12": "receiver_supply_p12vdc",
        "XPHT": "xmit_chassis_tmp",
        "XAHT": "xmit_amp_tmp",
        "XAFW": "xmit_fwd_pwr",
        "XARW": "xmit_ref_pwr",
        "XP28": "xmit_supply_p28vdc",
        "XP05": "xmit_supply_p5vdc",
        "GRMD": "gps_receive_mode",
        "GDMD": "gps_discipline_mode",
        "PLLL": "npll_unlock",
        "HTMP": "receiver_hires_tmp",
        "HUMI": "receiver_humidity",
        "RBIA": "vdc_draw",
        "CRUN": "cpu_runtime"
    }
    radialDiagnosticsSQLColumns = {
        "AMP1": "loop1_amp_calc",
        "AMP2": "loop2_amp_calc",
        "PH13": "loop1_phase_calc",
        "PH23": "loop2_phase_calc",
        "CPH1": "loop1_phase_corr",
        "CPH2": "loop2_phase_corr",
        "SNF1": "loop1_css_noisefloor",
        "SNF2": "loop2_css_noisefloor",
        "SNF3": "mono_css_noisefloor",
        "SSN1": "loop1_css_snr",
        "SSN2": "loop2_css_snr",
        "SSN3": "mono_css_snr",
        "DGRC": "diag_range_cell",
        "DOPV": "valid_doppler_cells",
        "DDAP": "dual_angle_pcnt",
        "RADV": "rad_vect_count",
        "RAPR": "avg_rads_per_range",
        "RARC": "nrange_proc",
        "RADR": "rad_range",
        "RMCV": "max_rad_spd",
        "RACV": "avg_rad_spd",
        "RABA": "avg_rad_bearing",
        "RTYP": "rad_type",
        "STYP": "spectra_type"
    }
    radialMetaSQLColumns = {
        "TransmitCenterFreqMHz": "cfreq",
        "RangeResolutionKMeters": "range_res",
        "TableRows": "nrads",
        "DopplerResolutionHzPerBin": "dres",
        "Manufacturer": "manufacturer",
        "TransmitSweepRateHz": "xmit_sweep_rate",
        "TransmitBandwidthKHz": "xmit_bandwidth",
        "CurrentVelocityLimit": "max_curr_lim",
        "RadialMinimumMergePoints": "min_rad_vect_pts",
        "BraggSmoothingPoints": "bragg_smooth_pts",
        "RadialBraggPeakDropOff": "rad_bragg_peak_dropoff",
        "BraggHasSecondOrder": "second_order_bragg",
        "RadialBraggPeakNull": "rad_bragg_peak_null",
        "RadialBraggNoiseThreshold": "rad_bragg_noise_thr",
        "CTF": "ctf_ver",
        "SpectraRangeCells": "spec_range_cells",
        "SpectraDopplerCells": "spec_doppler_cells",
        "FirstOrderCalc": "first_order_calc",
        "MergedCount": "nmerge_rads",
        "RangeStart": "range_bin_start",
        "RangeEnd": "range_bin_end"
    }
    old_radialMetaSQLColumns = {  #"format": "",
        "lat": "Origin",
        "lon": "Origin",
        "cfreq": "TransmitCenterFreqMHz",
        "range_res": "RangeResolutionKMeters",
        "ref_bearing": "ReferenceBearing",
        "nrads": "TableRows",
        "dres": "DopplerResolutionHzPerBin",
        "manufacturer": "Manufacturer",
        "xmit_sweep_rate": "TransmitSweepRateHz",
        "xmit_bandwidth": "TransmitBandwidthKHz",
        "max_curr_lim": "CurrentVelocityLimit",
        "min_rad_vect_pts": "RadialMinimumMergePoints",
        "loop1_amp_corr": "PatternAmplitudeCorrections",
        "loop2_amp_corr": "PatternAmplitudeCorrections",
        "loop1_phase_corr": "PatternPhaseCorrections",
        "loop2_phase_corr": "PatternPhaseCorrections",
        "bragg_smooth_pts": "BraggSmoothingPoints",
        "rad_bragg_peak_dropoff": "RadialBraggPeakDropOff",
        "second_order_bragg": "BraggHasSecondOrder",
        "rad_bragg_peak_null": "RadialBraggPeakNull",
        "rad_bragg_noise_thr": "RadialBraggNoiseThreshold",
        "music_param_01": "RadialMusicParameters",
        "music_param_02": "RadialMusicParameters",
        "music_param_03": "RadialMusicParameters",
        "ellip": "GreatCircle",
        #"earth_radius" : "",
        "ellip_flatten": "GreatCircle",
        "ctf_ver": "CTF",
        "lluvspec_ver": "LLUVSpec",
        "geod_ver": "GeodVersion",
        "patt_date": "PatternDate",
        "patt_res": "PatternResolution",
        "patt_smooth": "PatternSmoothing",
        "spec_range_cells": "SpectraRangeCells",
        "spec_doppler_cells": "SpectraDopplerCells",
        #"curr_ver" : "",
        #"codartools_ver" : "",
        "first_order_calc": "FirstOrderCalc",
        "lluv_tblsubtype": "TableType",
        #"proc_by" : "",
        "merge_method": "MergeMethod",
        "patt_method": "PatternMethod",
        #"dir" : "",
        #"dfile" : "",
        #"mtime" : "",
        #"sampling_period_hrs" : "",
        "nmerge_rads": "MergedCount",
        "range_bin_start": "RangeStart",
        "range_bin_end": "RangeEnd",
        "loop1_amp_calc": "PatternAmplitudeCalculations",
        "loop2_amp_calc": "PatternAmplitudeCalculations",
        "loop1_phase_calc": "PatternPhaseCalculations",
        "loop2_phase_calc": "PatternPhaseCalculations"}
    processingToolData = {
        "RadialMerger": "rad_merger_ver",
        "SpectraToRadial": "spec2rad_ver",
        "RadialSlider": "rad_slider_ver",
        "RadialArchiver": "rad_archiver_ver",
        "codar_rb2lluv.pl": "codartools_ver",  # not sure if this is correct
        "Currents": "curr_ver",
        "ProcessedTimeStamp": "proc_time"
    }

    def __init__(self, file_info, file_data):
        """The constructor for the BaseFile class.

        :param file_info: all info extracted from the file name
        :param file_data: all data extracted from the file
        """
        self.filename = file_info['filename']
        self.file_info = file_info
        self.file_data = file_data

    def valid_ctf_data(self):
        """Checks to see if the file starts with CTF

        :return: True if valid, False otherwise
        """
        pattern = "%CTF"
        result = False
        for line in self.file_data:
            if pattern in line:
                result = True
                break

        if not result:
            logger.error("CTF not found")
            return False
        return True

    def valid_site_metadata(self):
        """Checks to see if the site metadata is in the file.

        :return: True/False
        """
        pattern = re.compile(r'^\s*%Site:\s+\w{3,4}')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("Site metadata not found")
            return False
        return True

    def valid_timestamp_metadata(self):
        """Checks if the TimeStamp metadata is valid.

        :return: True/False
        """
        pattern = re.compile(
            r'^\s*%TimeStamp:\s+[0-9]{4}\s+[0-9]{1,2}\s+[0-9]{1,2}\s+[0-9]{1,2}\s+[0-9]{1,2}\s+[0-9]{1,2}')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("TimeStamp metadata not found or invalid")
            return False
        return True

    def valid_timezone_metadata(self):
        """Checks if the TimeZone metadata is valid.

        :return: True/False
        """
        pattern = re.compile(r'^\s*%TimeZone:\s+(\")?(GMT|UTC)(\")?')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("TimeZone metadata not found or invalid")
            return False
        return True

    def valid_latlon_metadata(self):
        """Checks if the Lat/Lon metadata is valid.

        :return: True/False
        """
        # %Origin:  32.5359167 -117.1222667
        pattern = re.compile(r'^\s*%Origin:')
        result_line = None
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result_line = line
                break

        if result_line is None:
            logger.error("Lat/Lon metadata not found or invalid")
            return False

        key, lat, lon = result_line.split()

        try:
            if float(lat) < -90 or float(lat) > 90 or float(lon) < -180 or float(lon) > 180:
                logger.error("Invalid lat,lon: %s,%s", lat, lon)
                return False
        except ValueError:
            logger.error("Invalid lat,lon: %s,%s", lat, lon)
            return False
        return True

    def _get_headers(self, line):
        """Return the headers for a particular section as an array with the header name being the value
        and the key being the index

        :param line: Line containing the header
        :return: list Returns the header name as the value and the key being the index
        """
        line_split = line.split(":")
        return line_split[1].strip().split(" ")

    def _get_table_data(self, start, end, starttime=None, endtime=None):
        """Get the data for a specific table type

        :param start: The Start of the table e.g. '%TableType: rads rad1'
        :param end: The end of the table e.g. '%TableEnd: 2'
        :param starttime: optional Epoch start time
        :param endtime: optional Epoch end time

        :return: array of dicts with keys being the column type (TIME,AMP1...) and values being the data
        """
        headers = []
        alldata = []
        header_found = False

        if len(self.file_data) == 0: return None

        # get the table data from between the passed in start/end of the table
        start_ind = None
        end_ind = None
        p1 = re.compile(start)
        p2 = re.compile(end)
        for i, line in enumerate(self.file_data):
            if start_ind and end_ind:
                break

            # match found on given line if search returns not None
            if p1.search(line) is not None:
                start_ind = i
            # search for table end only once table start was found and we are past that line/index:
            if p2.search(line) is not None and start_ind is not None:
                end_ind = i

        if start_ind is None or end_ind is None:
            return None

        table_data = self.file_data[start_ind + 1: end_ind]

        for line in table_data:
            # Look for header while not found (we need the headers to be able to parse the data)
            if not header_found:
                p = re.compile(r'%TableColumnTypes')
                if p.match(line):
                    headers = self._get_headers(line)
                    header_found = True
            else:
                # Data
                line = line.strip("%")
                line = line.strip()
                if len(headers) == len(line.split(None)):
                    # if line contains inf or nan, insert 'DEFAULT' use default values for those
                    if "inf" in line:
                        line = line.replace('inf', 'DEFAULT')
                    if "nan" in line:
                        line = line.replace('nan', 'DEFAULT')
                    if "-999.00" in line:
                        line = line.replace('-999.00', 'DEFAULT')
                    if "999.00" in line:
                        line = line.replace('999.00', 'DEFAULT')
                    if "1080.0" in line:
                        line = line.replace('1080.0', 'DEFAULT')

                    data = line.split(None)
                    # Skip anything that isn't our data
                    # TODO this line makes any lines that start with DEFAULT value get dropped, may or may not be an
                    #  issue in the future
                    if not is_number(data[0]): continue
                    data = dict(zip(headers, data))

                    # Get the date only for wave diagnostics and hardware diag.  lluv data doesn't have dates
                    if "THRS" in data:

                        # Make sure the dates and times are correct
                        # RDL_i_BML_BML1_2016_10_20_0900.hfrss10lluv has some weird values
                        try:
                            date = datetime.datetime(int(data["TYRS"]), int(data["TMON"]), int(data["TDAY"]),
                                                     int(data["THRS"]), int(data["TMIN"]), int(data["TSEC"]),
                                                     tzinfo=datetime.timezone.utc).timestamp()
                        except:
                            logger.error(f"_get_table_data() datetime(){line}")
                            continue

                        if starttime is not None:
                            if date < starttime: continue
                        if endtime is not None:
                            if date > endtime: continue

                    alldata.append(data)

        return alldata

    def _get_year_month(self):
        """Get the year and month from the filename

        :return: str <year>-<month> (2018-08)
        """
        file_base = self.filename.split('.')[0].split('_')
        year = file_base[2]
        month = file_base[3]
        return f"{year}-{month}"

    def get_file_timestamp(self):
        """Get the date/time from the filename

        :return: epoch timestamp of file date/time
        """
        file_base = self.filename.split('.')[0].split('_')
        year = file_base[2]
        month = file_base[3]
        day = file_base[4]
        time = re.findall('..', file_base[5])
        return datetime.datetime(int(year), int(month), int(day), int(time[0]), int(time[1]), 0,
                                 tzinfo=datetime.timezone.utc).timestamp()

    def _check_value(self, variable, data):
        """Check to see if a key is in a dictionary and if so, return the value or a default value

        :param variable: variable to check for
        :param data: dictionary containing values
        :return: empty string or value
        """
        if variable in data:
            return f"'{data[variable]}'"
        else:
            return "DEFAULT"

    def _check_float_value(self, variable, data, precision=2):
        """Check a key in a dictionary and return as float if it exists

        :param variable: key variable to check for
        :param data: dictionary containing values
        :param precision: number of decimal places (default 2)
        :return: None or float value
        """
        val = self._check_value(variable, data)
        if val == "'-'" or val == "-" or val == "nan" or val == "'DEFAULT'" or val == "DEFAULT":
            return 'DEFAULT'
        else:
            try:
                myval = "{:.{}f}".format(float(data[variable]), precision)
            except Exception as e:
                logger.error(f"check_float_value(): Error converting {data[variable]} to float.")
                return None
            return myval

    def _check_int_value(self, variable, data):
        """Check a key in a dictionary and return as int if it exists

        :param variable: key variable to check for
        :param data: dictionary containing values
        :return: None or int value
        """
        val = self._check_value(variable, data)
        if val == "'-'" or val == "-" or val == "nan" or val == "'DEFAULT'" or val == "DEFAULT":
            return 'DEFAULT'
        else:
            try:
                myval = int(float(data[variable]))
            except Exception as e:
                logger.error(f"check_int_value(): Error converting {data[variable]} to int.")
                return None
            return myval