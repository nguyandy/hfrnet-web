from basefile import BaseFile
import re
import logging
import datetime

logger = logging.getLogger(__name__)

class RadialFile(BaseFile):

    def __init__(self, file_info, file_data, timestamp):
        """The constructor for the RadialFile class.

        :param file_info: all info extracted from the file name
        :param file_data: all data extracted from the file
        :param timestamp: last modified timestamp of the file (from s3)
        """
        super().__init__(file_info, file_data)
        self.lastmodified = timestamp
        self.is_codar = self.is_codar_file(file_data)
        self.patterntype_override = None

        # set override on radial pattern type if needed
        if file_info['patterntype_override']:
            if file_info['patterntype_override'] == 'RDLi':
                self.patterntype_override = 'i'
            elif file_info['patterntype_override'] == 'RDLm':
                self.patterntype_override = 'm'
            else:
                self.patterntype_override = None
        else:
            self.patterntype_override = None

    def is_codar_file(self, file_data):
        """Checks to see if the radial files are from a codar site

        :return: true/false
        """
        pattern = re.compile(r'%Manufacturer:.*CODAR')
        for line in file_data:
            match = pattern.search(line)
            if match is not None: return True

        return False

    def get_pattern_type(self):
        """Returns the patern type m/i
        :return: str i/m
        """

        # use pattern type override if set
        if self.patterntype_override:
            return self.patterntype_override

        found = False
        found_line = ''
        pattern = re.compile(r'^\s*%PatternType:')
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                found = True
                found_line = line
                break

        # Some Wera (SC) have no pattern type, return i
        if not found: return "i"

        pattern = re.compile(r'^\s*%PatternType:\s+([A-Za-z]{3,})')
        match = pattern.search(found_line)
        if match.group(1).lower() == "measured":
            return "m"
        else:
            return "i"

    def valid_patterntype_metadata(self):
        """Checks if the PatternType metadata is valid.
    
        :return: True/False
        """
        pattern = re.compile(r'^\s*%PatternType:\s+[A-Za-z]{3,}')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("PatternType metadata not found or invalid")
            return False
        return True

    def valid_resolution(self):
        """Checks if the RangeResolution metadata is valid

        :return: True/False
        """
        pattern = re.compile(r'^\s*%RangeResolution.+[0-9]')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("Resolution metadata not found or invalid")
            return False
        return True

    def valid_lluv_rdltable(self):
        """Checks if the LLUV RDL table type is valid.
    
        :return: True/False
        """
        pattern = re.compile(r'^\s*%TableType:\s+LLUV\s+RDL')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("Table type LLUV RDL not defined")
            return False

        pattern = re.compile(r'^\s*[^%]')
        result = False
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result = True
                break

        if not result:
            logger.error("No radial data found")
            return False

        # get radial tables (between "%TableType" and "%TableEnd"

        start_ind = None
        end_ind = None
        p1 = re.compile(r'%TableType')
        p2 = re.compile(r'%TableEnd')
        for i, line in enumerate(self.file_data):
            if start_ind is not None and end_ind is not None:
                break
            if p1.search(line) is not None:
                start_ind = i
            if p2.search(line) is not None:
                end_ind = i

        # ensure both start and end patterns were found
        if start_ind is None or end_ind is None:
            logger.error("Radial tables not found")
            return False

        table_data = self.file_data[start_ind + 1: end_ind]

        pattern = re.compile(r'%TableColumns:\s+([0-9]+)')
        columns = None
        for line in table_data:
            match = pattern.search(line)
            if match is not None:
                columns = match.group(1)
                break
        if columns is None:
            logger.error("TableColumns not found")
            return False

        pattern = re.compile(r'%TableColumnTypes:.+\n')
        column_types = None
        for line in table_data:
            match = pattern.search(line)
            if match is not None:
                column_types = len(match.group(0).split())
                break
        if column_types is None:
            logger.error("TableColumnTypes not found")
            return False

        if str(column_types - 1) != str(columns):
            logger.error("Number of columns does not match TableColumns")
            return False

        return True

    def validate_file(self):
        """Checks if the radial file is valid. Performs all of the valid* functions.
    
        :return: True/False
        """
        if not self.valid_ctf_data(): return False
        if not self.valid_site_metadata(): return False
        if not self.valid_timestamp_metadata(): return False
        if not self.valid_timezone_metadata(): return False
        if not self.valid_latlon_metadata(): return False
        if not self.valid_lluv_rdltable(): return False
        if not self.valid_resolution(): return False

        if self.is_codar:
            if not self.valid_patterntype_metadata(): return False

        return True

    def get_radial_diagnostics(self):
        """Gets the data from the 'rads rad' table.

        :return: dict with keys being the column type (TIME,AMP1...) and values being the data
        """
        return self._get_table_data("%TableType: rads rad", "%TableEnd")

    def get_hardware_diagnostics(self):
        """Gets the data from the 'rcvr rcv' table.

        :return: dict with keys being the column type (TIME,AMP1...) and values being the data
        """
        data = self._get_table_data("%TableType: rcvr rcv", "%TableEnd")
        return data

    def get_process_info(self, sql=True):
        """Gets the data containing text with 'Process', generally the end of the file.

        :param sql: True will return the data using the sql column name as the key. Default true
        :return: dict with keys being the column type (TIME,AMP1...) and values being the data
        """
        data = {}

        pattern = re.compile(r'Process')
        result_lines = []
        for line in self.file_data:
            match = pattern.search(line)
            if match is not None:
                result_lines.append(line)

        if len(result_lines) == 0:
            return data

        for line in result_lines:
            line = line.strip()
            if "ProcessedTimeStamp" in line:
                try:
                    arr = line.split()
                    # If the seconds/minutes equals 60, then change it to 59
                    if int(arr[5]) == 60: arr[5] = 59
                    if int(arr[6]) == 60: arr[6] = 59
                    data["ProcessedTimeStamp"] = datetime.datetime(int(arr[1]), int(arr[2]), int(arr[3]), int(arr[4]),
                                                                   int(arr[5]), int(arr[6]),
                                                                   tzinfo=datetime.timezone.utc).timestamp()
                except:
                    logger.error(
                        f"get_process_info(): There was an error processing the timestamp: {line}.  Skipping line")
                    continue
            else:
                arr = line.split()
                data[arr[1].replace('"', '')] = arr[2]

        if not sql: return data

        return self.convert_meta_to_sql(data, self.processingToolData)

    def get_radial_metadata(self, sql=True):
        """Gets the metadata from the radial file

        :param sql: will return the data using the sql column name as the key.  Default true
        :return: dict with keys being the column type (TIME,AMP1...) and values being the data
        """
        data = {}
        start = r'%CTF'
        end = r'%TableStart'

        if len(self.file_data) == 0: return None

        # get the table data from between the passed in start/end of the table
        start_ind = None
        end_ind = None
        p1 = re.compile(start)
        p2 = re.compile(end)
        for i, line in enumerate(self.file_data):
            if start_ind is not None and end_ind is not None:
                break
            if p1.search(line) is not None:
                start_ind = i
            if p2.search(line) is not None:
                end_ind = i

        # ensure both start and end patterns were found
        if start_ind is None or end_ind is None:
            return None

        table_metadata = self.file_data[start_ind: end_ind]
        if len(table_metadata) == 1: return None

        for line in table_metadata:
            line = line.strip("%")
            line = line.strip("%")
            line = line.split(":", 1)
            if len(line) == 2:
                data[line[0]] = line[1].strip()

        if not sql: return data

        data_sql = {}
        for key, value in data.items():
            if "nan" in value:
                logger.warning("invalid entry %s for %s", value, key)
                continue

            arr = value.split()

            if key == "RangeResolutionMeters":
                data_sql["range_res"] = '%.4f' % (float(arr[0]) / 1000)
            elif key == "LLUVSpec":
                data_sql["lluvspec_ver"] = arr[0]
            elif key == "Origin":
                data_sql["lat"] = '%.6f' % (float(arr[0]))
                data_sql["lon"] = '%.6f' % (float(arr[1]))
            elif key == "GreatCircle":
                data_sql["ellip"] = arr[0].strip('"')
                # Remove characters that aren't numbers or a period
                # Mainly for WHOI:LPWR - have a single quote at the end
                non_decimal = re.compile(r'[^\d.]+')
                data_sql["ellip_flatten"] = arr[2]
                data_sql["ellip_flatten"] = non_decimal.sub("", arr[2])
            elif key == "GeodVersion":
                pattern = re.compile(r'(\".+\")\s+(\d+\.\d+)\s+')
                arr = pattern.findall(value)
                data_sql["geod_ver"] = arr[0][0].strip('"') + " " + arr[0][1]

            elif key == "ReferenceBearing":
                data_sql["ref_bearing"] = '%.4f' % (float(arr[0]))
            elif key == "PatternDate":
                data_sql["patt_date"] = datetime.datetime(int(arr[0]), int(arr[1]), int(arr[2]), int(arr[3]),
                                                          int(arr[4]), int(arr[5]),
                                                          tzinfo=datetime.timezone.utc).timestamp()
            elif key == "PatternResolution":
                data_sql["patt_res"] = '%.2f' % (float(arr[0]))
            elif key == "PatternSmoothing":
                data_sql["patt_smooth"] = '%.2f' % (float(arr[0]))
            elif key == "PatternAmplitudeCorrections":
                data_sql["loop1_amp_corr"] = '%.2f' % (float(arr[0]))
                data_sql["loop2_amp_corr"] = '%.2f' % (float(arr[1]))
            elif key == "PatternPhaseCorrections":
                data_sql["loop1_phase_corr"] = '%.2f' % (float(arr[0]))
                data_sql["loop2_phase_corr"] = '%.2f' % (float(arr[1]))
            elif key == "PatternAmplitudeCalculations":
                data_sql["loop1_amp_calc"] = '%.2f' % (float(arr[0]))
                data_sql["loop2_amp_calc"] = '%.2f' % (float(arr[1]))

            elif key == "PatternPhaseCorrections":
                data_sql["loop1_phase_corr"] = '%.2f' % (float(arr[0]))
                data_sql["loop2_phase_corr"] = '%.2f' % (float(arr[1]))

            elif key == "PatternAmplitudeCalculations":
                data_sql["loop1_amp_calc"] = '%.2f' % (float(arr[0]))
                data_sql["loop2_amp_calc"] = '%.2f' % (float(arr[1]))

            elif key == "PatternPhaseCalculations":
                data_sql["loop1_phase_calc"] = '%.2f' % (float(arr[0]))
                data_sql["loop2_phase_calc"] = '%.2f' % (float(arr[1]))

            elif key == "RadialMusicParameters":
                data_sql["music_param_01"] = '%.2f' % (float(arr[0]))
                data_sql["music_param_02"] = '%.2f' % (float(arr[1]))
                data_sql["music_param_03"] = '%.2f' % (float(arr[2]))

            elif key == "MergeMethod":
                data_sql["merge_method"] = arr[0]

            elif key == "TableType":
                data_sql["lluv_tblsubtype"] = arr[1]

            elif key == "TimeStamp":
                data_sql["time"] = datetime.datetime(int(arr[0]), int(arr[1]), int(arr[2]), int(arr[3]), int(arr[4]),
                                                     int(arr[5]), tzinfo=datetime.timezone.utc).timestamp()

                data_sql["endtime"] = datetime.datetime(int(arr[0]), int(arr[1]), int(arr[2]), int(arr[3]), int(arr[4]),
                                                        int(arr[5]), tzinfo=datetime.timezone.utc).timestamp()

            elif key == "TimeCoverage":
                data_sql["sampling_period_hrs"] = '%.4f' % (float(arr[0]) / 60)

            elif key == "PatternMethod":
                data_sql["patt_method"] = arr[0]

            else:
                if key in self.radialMetaSQLColumns:
                    data_sql[self.radialMetaSQLColumns[key]] = value
                else:
                    data_sql[key] = value
        # end for key, value in data
        return data_sql

    def get_radialfile_metadata(self):
        """Combines the data from radial metadata and process info

        :return: dict with keys being the column type (TIME,AMP1...) and values being the data
        """
        z = self.get_radial_metadata(self.file_data).copy()
        z.update(self.get_process_info(self.file_data))
        return z

    def convert_meta_to_sql(self, lluv_data, sql_columns):
        """Converts variables from the radial files to their respective sql variable name
  
        :param lluv_data: array containing the lluv variables
        :param sql_columns: array containing the mapped sql columns
        :return: dict with keys being the column type (TIME,AMP1...) and values being the data
        """
        sql_data = {}
        if lluv_data is None: return sql_data
        for key, value in lluv_data.items():
            if key in sql_columns:
                sql_data[sql_columns[key]] = lluv_data[key]
            else:
                logger.debug(key + " not found in convert_meta_to_sql()")
                sql_data[key] = lluv_data[key]
        return sql_data

    def get_hardware_diagnostics_sql(self, site_id, network_id):
        """
        Returns the sql used to insert hardware diagnostics data into the database

        :param site_id: id of the station extracted from the hfradar db
        :param network_id: id of the network extracted from the hfradar db
        :return: list of SQL insert statements for hardware diagnostics
        """
        my_lists = []
        datas = self.get_hardware_diagnostics()
        if not datas: return datas

        for data in datas:
            mydate = datetime.datetime(int(data["TYRS"]), int(data["TMON"]), int(data["TDAY"]), int(data["THRS"]),
                                       int(data["TMIN"]), int(data["TSEC"]), tzinfo=datetime.timezone.utc).timestamp()

            my_lists.append(
                f"({site_id}, "
                f"{network_id}, "
                f"{mydate}, "
                f"{self._check_int_value('RTMP', data)}, "
                f"{self._check_int_value('MTMP', data)}, "
                f"{self._check_value('XTRP', data)}, "
                f"{self._check_int_value('RUNT', data)}, "
                f"{self._check_float_value('SP24', data)}, "
                f"{self._check_float_value('SP05', data)}, "
                f"{self._check_float_value('SN05', data)}, "
                f"{self._check_float_value('SP12', data)}, "
                f"{self._check_int_value('XPHT', data)}, "
                f"{self._check_int_value('XAHT', data)}, "
                f"{self._check_int_value('XAFW', data)}, "
                f"{self._check_int_value('XARW', data)}, "
                f"{self._check_float_value('XP28', data)}, "
                f"{self._check_float_value('XP05', data)}, "
                f"{self._check_int_value('GRMD', data)}, "
                f"{self._check_int_value('GDMD', data)}, "
                f"{self._check_int_value('PLLL', data)}, "
                f"{self._check_float_value('HTMP', data)}, "
                f"{self._check_int_value('HUMI', data)}, "
                f"{self._check_float_value('RBIA', data)}, "
                f"{self._check_float_value('CRUN', data)})"
            )

        return my_lists

    def get_radialdiagnostics_sql(self, site_id, network_id):
        """
        Returns the sql used to insert Radial diagnostics data into the database

        :param site_id: id of the station extracted from the hfradar db
        :param network_id: id of the network extracted from the hfradar db
        :return: list of SQL insert statements for radial diagnostics
        """
        my_lists = []
        datas = self.get_radial_diagnostics()
        if not datas: return datas

        # Get pattern type
        patterntype = self.get_pattern_type()

        for data in datas:
            mydate = datetime.datetime(int(data["TYRS"]), int(data["TMON"]), int(data["TDAY"]), int(data["THRS"]),
                                       int(data["TMIN"]), int(data["TSEC"]), tzinfo=datetime.timezone.utc).timestamp()

            my_lists.append(
                f"({site_id}, "
                f"{network_id}, "
                f"{mydate}, "
                f"'{patterntype}', "
                f"{self._check_float_value('AMP1', data, 4)}, "
                f"{self._check_float_value('AMP2', data, 4)}, "
                f"{self._check_float_value('PH13', data, 4)}, "
                f"{self._check_float_value('PH23', data, 4)}, "
                f"{self._check_float_value('CPH1', data, 4)}, "
                f"{self._check_float_value('CPH2', data, 4)}, "
                f"{self._check_float_value('SNF1', data, 4)}, "
                f"{self._check_float_value('SNF2', data, 4)}, "
                f"{self._check_float_value('SNF3', data, 4)}, "
                f"{self._check_float_value('SSN1', data)}, "
                f"{self._check_float_value('SSN2', data)}, "
                f"{self._check_float_value('SSN3', data)}, "
                f"{self._check_int_value('DGRC', data)}, "
                f"{self._check_int_value('DOPV', data)}, "
                f"{self._check_int_value('DDAP', data)}, "
                f"{self._check_int_value('RADV', data)}, "
                f"{self._check_int_value('RAPR', data)}, "
                f"{self._check_int_value('RARC', data)}, "
                f"{self._check_float_value('RADR', data)}, "
                f"{self._check_float_value('RMCV', data, 4)}, "
                f"{self._check_float_value('RACV', data, 4)}, "
                f"{self._check_float_value('RABA', data, 4)}, "
                f"{self._check_int_value('RTYP', data)}, "
                f"{self._check_int_value('STYP', data)})"
            )

        return my_lists

    def get_radial_meta_sql(self, site_id, network_id):
        """
        Returns the sql used to insert radialfile data into the database

        :param site_id: id of the station extracted from the hfradar db
        :param network_id: id of the network extracted from the hfradar db
        :return: list of SQL insert statements for the radial database
        """
        my_lists = []
        data = self.get_radialfile_metadata()
        if not data: return data

        # Get file extension
        extension = self.filename.split('.')[1]

        # Get pattern type
        patterntype = self.get_pattern_type()

        finaldir = f"/radials/{self.file_info['site']}/{self.file_info['year']}-{self.file_info['month']}"
        dirfile = finaldir + f"/{self.file_info['filename']}"

        my_lists.append(
            f"({site_id}, "
            f"{network_id}, "
            f"{self._check_float_value('time', data)}, "
            f"'{extension}', "
            f"'{patterntype}', "
            f"{self._check_float_value('lat', data, 6)}, "
            f"{self._check_float_value('lon', data, 6)}, "
            f"{self._check_float_value('cfreq', data, 11)}, "
            f"{self._check_float_value('range_res', data, 4)}, "
            f"{self._check_float_value('ref_bearing', data, 4)}, "
            f"{self._check_float_value('nrads', data)}, "
            f"{self._check_float_value('dres', data, 8)}, "
            f"{self._check_value('manufacturer', data)}, "
            f"{self._check_float_value('xmit_sweep_rate', data, 8)}, "
            f"{self._check_float_value('xmit_bandwidth', data, 8)}, "
            f"{self._check_float_value('max_curr_lim', data, 8)}, "
            f"{self._check_float_value('min_rad_vect_pts', data)}, "
            f"{self._check_float_value('loop1_amp_corr', data)}, "
            f"{self._check_float_value('loop2_amp_corr', data)}, "
            f"{self._check_float_value('loop1_phase_corr', data)}, "
            f"{self._check_float_value('loop2_phase_corr', data)}, "
            f"{self._check_float_value('bragg_smooth_pts', data)}, "
            f"{self._check_float_value('rad_bragg_peak_dropoff', data)}, "
            f"{self._check_float_value('second_order_bragg', data)}, "
            f"{self._check_float_value('rad_bragg_peak_null', data)}, "
            f"{self._check_float_value('rad_bragg_noise_thr', data)}, "
            f"{self._check_float_value('music_param_01', data)}, "
            f"{self._check_float_value('music_param_02', data)}, "
            f"{self._check_float_value('music_param_03', data)}, "
            f"{self._check_value('ellip', data)}, "
            f"{self._check_float_value('earth_radius', data, 15)}, "
            f"{self._check_float_value('ellip_flatten', data, 9)}, "
            f"{self._check_value('rad_merger_ver', data)}, "
            f"{self._check_value('spec2rad_ver', data)}, "
            f"{self._check_value('ctf_ver', data)}, "
            f"{self._check_value('lluvspec_ver', data)}, "
            f"{self._check_value('geod_ver', data)}, "
            f"{self._check_value('rad_slider_ver', data)}, "
            f"{self._check_value('rad_archiver_ver', data)}, "
            f"{self._check_float_value('patt_date', data)}, "
            f"{self._check_float_value('patt_res', data)}, "
            f"{self._check_float_value('patt_smooth', data)}, "
            f"{self._check_float_value('spec_range_cells', data)}, "
            f"{self._check_float_value('spec_doppler_cells', data)}, "
            f"{self._check_value('curr_ver', data)}, "
            f"{self._check_value('codartools_ver', data)}, "
            f"{self._check_float_value('first_order_calc', data)}, "
            f"{self._check_value('lluv_tblsubtype', data)}, "
            f"{self._check_value('proc_by', data)}, "
            f"{self._check_float_value('merge_method', data)}, "
            f"{self._check_float_value('patt_method', data)}, "
            f"'{finaldir}', "
            f"'{dirfile}', "
            f"{self.lastmodified}, "
            f"{self._check_float_value('sampling_period_hrs', data, 4)}, "
            f"{self._check_float_value('nmerge_rads', data)}, "
            f"{self._check_float_value('proc_time', data)}, "
            f"{self._check_float_value('range_bin_start', data)}, "
            f"{self._check_float_value('range_bin_end', data)}, "
            f"{self._check_float_value('loop1_amp_calc', data)}, "
            f"{self._check_float_value('loop2_amp_calc', data)}, "
            f"{self._check_float_value('loop1_phase_calc', data)}, "
            f"{self._check_float_value('loop2_phase_calc', data)}, "
            f"'{datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')}')"
        )

        return my_lists

    def insert_into_db(self, db, site_id, network_id):
        """Inserts the radial file into the database

        :param db: instance of DataBase with an active connection
        :param site_id: id of the site used for this radial file
        :param network_id: id of the network used for this radial file
        :return: True/False depending on whether it works or not
        """
        # Hardware diagnostics
        logger.debug("insert_into_db(): Getting hardware diagnostics data")
        hd = self.get_hardware_diagnostics_sql(site_id, network_id)
        if hd:
            with db.connection.cursor() as cur:
                s = ","
                sql = f"replace into hardwarediag values {s.join(hd)}"
                try:
                    cur.execute(sql)
                    db.connection.commit()
                except Exception as e:
                    logger.error(f"Unable to insert hardwarediag data: {e}")
                    return False
        else:
            logger.warning(f"No hardware diagnostics data found in file {self.filename}")

        # Radial Diagnostics
        logger.debug("insert_into_db(): Getting radial diagnostics data")
        rd = self.get_radialdiagnostics_sql(site_id, network_id)
        if rd:
            with db.connection.cursor() as cur:
                s = ","
                sql = f"replace into radialdiag values {s.join(rd)}"
                try:
                    cur.execute(sql)
                    db.connection.commit()
                except Exception as e:
                    logger.error(f"Unable to insert radialdiag data: {e}")
                    return False
        else:
            logger.warning(f"No radial diagnostics data found in file {self.filename}")

        # get radial file meta
        logger.debug("insert_into_db(): Getting radial meta data")
        results = self.get_radial_meta_sql(site_id, network_id)
        if results:
            with db.connection.cursor() as cur:
                s = ","
                sql = f"replace into radialfiles values {s.join(results)}"
                try:
                    cur.execute(sql)
                    db.connection.commit()
                except Exception as e:
                    logger.error(f"Unable to insert radialfiles data: {e}")
                    return False
        else:
            logger.warning(f"No radial meta data found in file {self.filename}")

        return True
