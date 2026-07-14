import unittest
from ..radialfile import RadialFile


class TestRadialFile(unittest.TestCase):
    """Tests for RadialFile class methods"""
    def setUp(self):
        # "bare minimum" valid file contents for a RadialFile
        # (used to test table validation and basic metadata parsing)
        self.minimum_rdl9_table = [
            '%CTF: 1.00\n',
            '%Manufacturer: CODAR Ocean Sensors. SeaSonde\n',
            '%Site: FORT\n',
            '%TimeStamp: 2025 05 13  06 00 00\n',
            '%PatternType: Measured\n',
            '%RangeResolutionKMeters: 0.999295\n',
            '%TimeZone: "UTC" +0.000 0\n',
            '%Origin: 39.3783667  -74.3990167\n',
            '%TableType: LLUV RDL9\n',
            '%TableColumns: 3\n',
            '%TableColumnTypes: COL1 COL2 COL3\n',
            '%TableRows: 4\n',
            '%TableStart:\n',
            ' 1 2 3\n',
            ' 4 5 6\n',
            ' 7 nan 999.00\n',
            ' 8 -999.00 1080.0\n',
            '%TableEnd:\n',
            '%%\n',
            '%%\n',
            '%TableType: rads rad3\n',
            '%TableColumns: 3\n',
            '%TableColumnTypes: COL1 COL2 COL3\n',
            '%TableRows: 4\n',
            '%TableStart: 2\n',
            ' 1 2 3\n',
            ' 4 5 6\n',
            ' 7 nan 999.00\n',
            ' 8 -999.00 1080.0\n',
            '%TableEnd:\n',
            '%%\n',
            '%%\n',
            '%TableType: rcvr rcv3\n',
            '%TableColumns: 3\n',
            '%TableColumnTypes: COL1 COL2 COL3\n',
            '%TableRows: 4\n',
            '%TableStart: 3\n',
            ' 1 2 3\n',
            ' 4 5 6\n',
            ' 7 nan 999.00\n',
            ' 8 -999.00 1080.0\n',
            '%TableEnd:\n',
            '%ProcessedTimeStamp: 2025 05 30  20 00 05\n',
            '%ProcessingTool: "LLUVMusic" 14.0.7\n',
            '%ProcessingTool: "LLUVCutoff" 4.0.1\n',
        ]

        # 'bad/garbage data' table used for testing (missing tablecolumns and tablecolumntypes)
        self.bad_data = [
            '%this is\n',
            '%bad data\n',
            '%TableType: WAVL WVM9\n',
            ' 1 2 3\n',
            ' 4 5 6\n',
            ' 7 nan 999.00\n',
            ' 8 -999.00 1080.0\n',
            '%TableEnd: \n',
        ]

        # test RadialFile objects
        self.f1 = RadialFile({
            'filename': 'RDL_KOK_2023_01_04_0000.ruv',
            'filetype': 'radial',
            'site': 'KOK',
            'affiliation': 'UH',
            'year': '2023',
            'month': '01',
            'timestamp': 1672790400,
        }, self.minimum_rdl9_table, 1672790400)

        self.f2 = RadialFile({
            'filename': 'RDLm_FORT_2025_05_13_0600.ruv',
            'filetype': 'radial',
            'site': 'FORT',
            'affiliation': 'CODAR',
            'year': '2025',
            'month': '05',
            'timestamp': 1747116000,
        }, self.bad_data, 1747116000)

    """file validation tests"""
    def test_is_codar_file(self):
        """Tests the is_codar_file method"""
        assert self.f1.is_codar_file(self.minimum_rdl9_table) is True
        assert self.f1.is_codar_file(self.bad_data) is False

    def test_get_pattern_type(self):
        """Tests the get_pattern_type method"""
        assert self.f1.get_pattern_type() == 'm'
        # no pattern type found should always return pattern i
        assert self.f2.get_pattern_type() == 'i'

    def test_valid_patterntype_metadata(self):
        """Tests the valid_patterntype_metadata method"""
        assert self.f1.valid_patterntype_metadata() is True
        # this method returns false when no paterntype metadata is found unlike get_pattern_type
        assert self.f2.valid_patterntype_metadata() is False

    def test_valid_resolution(self):
        """Tests the valid_resolution method"""
        assert self.f1.valid_resolution() is True
        assert self.f2.valid_resolution() is False

    def test_valid_lluv_rdltable(self):
        """Tests the valid_lluv_rdltable method"""
        assert self.f1.valid_lluv_rdltable() is True
        assert self.f2.valid_lluv_rdltable() is False


    """Table/data retrieval tests"""
    def test_get_radial_diagnostics(self):
        """Tests the get_radial_diagnostics method with a mock diagnostics table"""
        t1 = self.f1.get_radial_diagnostics()
        t2 = self.f2.get_radial_diagnostics()

        assert t1 == [
            {'COL1': '1', 'COL2': '2', 'COL3': '3'},
            {'COL1': '4', 'COL2': '5', 'COL3': '6'},
            {'COL1': '7', 'COL2': 'DEFAULT', 'COL3': 'DEFAULT'},
            {'COL1': '8', 'COL2': 'DEFAULT', 'COL3': 'DEFAULT'},
        ]
        assert t2 is None

    def test_get_hardware_diagnostics(self):
        """Tests the get_hardware_diagnostics method with a mock diagnostics table"""
        t1 = self.f1.get_hardware_diagnostics()
        t2 = self.f2.get_hardware_diagnostics()

        assert t1 == [
            {'COL1': '1', 'COL2': '2', 'COL3': '3'},
            {'COL1': '4', 'COL2': '5', 'COL3': '6'},
            {'COL1': '7', 'COL2': 'DEFAULT', 'COL3': 'DEFAULT'},
            {'COL1': '8', 'COL2': 'DEFAULT', 'COL3': 'DEFAULT'},
        ]
        assert t2 is None

    def test_get_process_info(self):
        """Test get_process_info method with basic info"""
        p1 = self.f1.get_process_info()
        p2 = self.f2.get_process_info()

        assert p1 == {
            #proc_time is the SQL insert name for ProcessedTimeStamp, so this tests that method as well indirectly
            'proc_time': 1748635205,
            'LLUVMusic': '14.0.7',
            'LLUVCutoff': '4.0.1',
        }
        # no process data in 'file'
        assert p2 == {}

    def test_get_radial_metadata(self):
        """Tests the get_radial_metadata method"""
        m1 = self.f1.get_radial_metadata()
        m2 = self.f2.get_radial_metadata()

        # some values are converted to their sql name, and lat/lon are rounded to precision 6 for sql insert
        assert m1 == {
            'ctf_ver': '1.00',
            'manufacturer': 'CODAR Ocean Sensors. SeaSonde',
            'Site': 'FORT',
            'time': 1747116000,
            'endtime': 1747116000,
            'PatternType': 'Measured',
            'range_res': '0.999295',
            'TimeZone': '"UTC" +0.000 0',
            'lat': '39.378367',
            'lon': '-74.399017',
            'lluv_tblsubtype': 'RDL9',
            'TableColumns': '3',
            'TableColumnTypes': 'COL1 COL2 COL3',
        }
        assert m2 is None


if __name__ == '__main__':
    unittest.main()