import unittest
from ..basefile import BaseFile, is_number
from ..acquisition import get_file_info


class TestBaseFile(unittest.TestCase):
    """Tests for BaseFile class methods"""
    def setUp(self):
        # "bare minimum" valid file contents for a BaseFile (used to test validation methods)
        self.minimum_valid_file_data = [
            '%CTF: 1.00\n',
            '%Site: FORT\n',
            '%TimeStamp: 2025 05 13  06 00 00\n',
            '%TimeZone: "UTC" +0.000 0\n',
            '%Origin: 39.3783667  -74.3990167\n',
            '%TableType: TEST TABL\n',
            '%TableColumnTypes: COL1 COL2 COL3\n',
            ' 1 2 3\n',
            ' 4 5 6\n',
            ' 7 nan 999.00\n',
            ' 8 -999.00 1080.0\n',
            '%TableEnd: \n',
        ]

        # 'bad/garbage data' table used for testing
        self.bad_data = [
            '%this is\n',
            '%bad data\n',
            '%TableType: BAD TABL\n',
            ' 1 2 3\n',
            ' 4 5 6\n',
            ' 7 nan 999.00\n',
            ' 8 -999.00 1080.0\n',
            '%NotATableEnd: \n',
        ]

        # call get_file_info for each test file
        f1_info = get_file_info('RDL_KOK_2023_01_04_0000.ruv')
        f2_info = get_file_info('RDLm_FORT_2025_05_13_0600.ruv')
        f3_info = get_file_info('WVLM_BIGC_2025_05_01_0000.wls')

        # test BaseFile objects using file_info and file_data from above
        self.f1 = BaseFile(f1_info, self.minimum_valid_file_data)
        self.f2 = BaseFile(f2_info, self.bad_data)
        self.f3 = BaseFile(f3_info, self.bad_data)

    def test_get_file_dir_info(self):
        """Test get_file_dir_info function with various file names

        Note that get_file_info is called during test setUp, so we do not need to call it explicitly here
        """

        assert self.f1.file_info == {
            'filename': 'RDL_KOK_2023_01_04_0000.ruv',
            'filetype': 'radial',
            'site': 'KOK',
            'affiliation': 'UH',
            'year': '2023',
            'month': '01',
            'timestamp': 1672790400,
        }

        assert self.f2.file_info == {
            'filename': 'RDLm_FORT_2025_05_13_0600.ruv',
            'filetype': 'radial',
            'site': 'FORT',
            'affiliation': 'CODAR',
            'year': '2025',
            'month': '05',
            'timestamp': 1747116000,
        }

        assert self.f3.file_info == {
            'filename': 'WVLM_BIGC_2025_05_01_0000.wls',
            'filetype': 'wave',
            'site': 'BIGC',
            'affiliation': 'CODAR',
            'year': '2025',
            'month': '05',
            'timestamp': 1746057600,
        }

    """file validation tests"""
    def test_valid_ctf_data(self):
        """Test valid_ctf_data method with valid/invalid data"""
        assert self.f1.valid_ctf_data()
        assert not self.f2.valid_ctf_data()

    def test_valid_site_metadata(self):
        """Test valid_site_metadata method with valid/invalid data"""
        assert self.f1.valid_site_metadata()
        assert not self.f2.valid_site_metadata()

    def test_valid_timestamp_metadata(self):
        """Test valid_timestamp_metadata method with valid/invalid data"""
        assert self.f1.valid_timestamp_metadata()
        assert not self.f2.valid_timestamp_metadata()

    def test_valid_timezone_metadata(self):
        """Test valid_timezone_metadata method with valid/invalid data"""
        assert self.f1.valid_timezone_metadata()
        assert not self.f2.valid_timezone_metadata()

    def test_valid_latlon_metadata(self):
        """Test valid_latlon_metadata method with valid/invalid data"""
        assert self.f1.valid_latlon_metadata()
        assert not self.f2.valid_latlon_metadata()

    """table data retrieval test"""
    def test_get_table_data(self):
        """Test get_table_data function with valid/invalid table data"""

        # should return nothing because table data in bad_data is not properly defined
        assert not self.f2._get_table_data('%TableType: BAD TABL', '%TableEnd:')

        data = self.f1._get_table_data('%TableType: TEST TABL', '%TableEnd:')
        assert data == [
            {'COL1': '1', 'COL2': '2', 'COL3': '3'},
            {'COL1': '4', 'COL2': '5', 'COL3': '6'},
            {'COL1': '7', 'COL2': 'DEFAULT', 'COL3': 'DEFAULT'},
            {'COL1': '8', 'COL2': 'DEFAULT', 'COL3': 'DEFAULT'},
        ]

    """tests for misc/helper functions"""
    def test_is_number(self):
        """Test is_number function with numbers and non-numbers"""
        assert is_number('10')
        assert not is_number('not10')

    def test_get_headers(self):
        """Test _get_headers method"""
        assert self.f1._get_headers(self.minimum_valid_file_data[6]) == ['COL1', 'COL2', 'COL3']

    def test_get_year_month(self):
        """Test _get_year_month method"""
        assert self.f1._get_year_month() == '2023-01'
        assert self.f2._get_year_month() == '2025-05'
        assert self.f3._get_year_month() == '2025-05'

    def test_get_file_timestamp(self):
        """Test get_file_timestamp method"""
        assert self.f1.get_file_timestamp() == 1672790400
        assert self.f2.get_file_timestamp() == 1747116000
        assert self.f3.get_file_timestamp() == 1746057600


if __name__ == '__main__':
    unittest.main()