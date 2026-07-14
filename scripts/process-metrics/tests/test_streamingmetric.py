import unittest
import configparser

from ..streamingmetric import get_file_info, get_site_bounds


class TestStreamingMetric(unittest.TestCase):
    """Tests for streamingmetric functions"""
    def setUp(self):

        self.test_filename_1 = [('--file', 'RDL_KOK_2023_01_04_0000.ruv')]
        self.test_filename_2 = [('--file', 'RDLm_FORT_2025_05_13_0600.ruv')]
        self.test_filename_3 = [('--file', 'RDLi_FLND_2025_05_10_1230.ruv')]


    def test_get_file_info(self):
        """Validates dict returned by get_file_info using test file names"""
        file_info = get_file_info(self.test_filename_1)

        assert file_info == {
            'filename': 'RDL_KOK_2023_01_04_0000.ruv',
            'type': 'wera',
            'site': 'KOK',
            'affiliation': 'UH',
            'year': '2023',
            'month': '01',
            'timestamp': 1672790400,
            'date_group': '2023-01-01',
            'report_rate': 60,
        }

        file_info = get_file_info(self.test_filename_2)
        assert file_info == {
            'filename': 'RDLm_FORT_2025_05_13_0600.ruv',
            'type': 'RDLm',
            'site': 'FORT',
            'affiliation': 'CODAR',
            'year': '2025',
            'month': '05',
            'timestamp': 1747116000,
            'date_group': '2025-05-01',
            'report_rate': 60,
        }

        file_info = get_file_info(self.test_filename_3)
        assert file_info == {
            'filename': 'RDLi_FLND_2025_05_10_1230.ruv',
            'type': 'RDLi',
            'site': 'FLND',
            'affiliation': 'ODU',
            'year': '2025',
            'month': '05',
            'timestamp': 1746880200,
            'date_group': '2025-05-01',
            'report_rate': 30,
        }


    def test_get_site_bounds(self):
        """Tests that the correct site bounds are returned using a few example sites"""

        # site: RDSR, affiliation: USF, range=100km
        lat, lon, radar_range = -82.83438330, 27.83253330, 100
        lonmin, lonmax, latmin, latmax = get_site_bounds(lat, lon, radar_range)

        assert lonmin == -83.8494493132451 and lonmax == -81.8193172867549
        assert latmin == 26.930084102201494 and latmax == 28.734864488316237

        # site: OLDB, affiliation: Rutgers, range=100km
        lat, lon, radar_range = -74.25350000, 40.46210000, 100
        lonmin, lonmax, latmin, latmax = get_site_bounds(lat, lon, radar_range)

        assert lonmin == -75.43246040957727 and lonmax == -73.07453959042273
        assert latmin == 39.56148196903537 and latmax == 41.36257729301214

        # site: VIEW, affiliation: ODU, range=100km
        lat, lon, radar_range = -76.24318330, 36.94993330, 100
        lonmin, lonmax, latmin, latmax = get_site_bounds(lat, lon, radar_range)

        assert lonmin == -77.36584387113234 and lonmax == -75.12052272886766
        assert latmin == 36.04877496658911 and latmax == 37.850954591192576


if __name__ == '__main__':
    unittest.main()
