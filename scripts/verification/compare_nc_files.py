import s3fs
import xarray as xr

# List files to open
s3 = s3fs.S3FileSystem(anon=True)
files = [
    's3://rps-nccf-hfrnet-dissemination-uat/hfrtv/2025/07/31/rtv-usegc-1km-uwls_v1r0_hfr_s202507310000000_e202507310000000_c202507310131334.nc',
    's3://rps-nccf-hfrnet-dissemination-uat/hfrtv/2025/07/31/rtv-usegc-1km-uwls_v1r0_hfr_s202507310000000_e202507310000000_c202507310200178.nc',
    's3://rps-nccf-hfrnet-dissemination-uat/hfrtv/2025/07/31/rtv-usegc-1km-uwls_v1r0_hfr_s202507310000000_e202507310000000_c202507310233390.nc',
    's3://rps-nccf-hfrnet-dissemination-uat/hfrtv/2025/07/31/rtv-usegc-1km-uwls_v1r0_hfr_s202507310000000_e202507310000000_c202507310333486.nc'
]

# Open each file and load into xarray datasets
netcdfs = []
for file in files:
    try:
        f = s3.open(file, mode='rb')
        ds = xr.open_dataset(f, engine='h5netcdf', cache=True)
        netcdfs.append(ds)
        print(f"Successfully opened dataset: {ds}")
    except Exception as e:
        print(f"Error opening file {file}: {e}")

# --- Comparison Script ---
import numpy as np

def compare_datasets(datasets):
    def print_presence_and_missing(sets, label, items):
        print(f"\n{label} present in all files:")
        common = set.intersection(*sets)
        print(common)
        print(f"\n{label} not present in all files:")
        for i, s in enumerate(sets):
            missing = items - s
            if missing:
                print(f"File {i+1} missing: {missing}")
        return common

    def compare_values_across_files(values, label, indent=""):
        if all(val == values[0] for val in values[1:]):
            print(f"{indent}{label}: Identical value across files.")
        else:
            print(f"{indent}{label}: Different values:")
            for i, val in enumerate(values):
                print(f"{indent}  File {i+1}: {val}")

    # Compare variable names
    var_sets = [set(ds.variables.keys()) for ds in datasets]
    all_vars = set.union(*var_sets)
    print("\nVariables in each file:")
    for i, ds in enumerate(datasets):
        print(f"File {i+1}: {list(ds.variables.keys())}")
    common_vars = print_presence_and_missing(var_sets, "Variables", all_vars)

    # Compare global attributes
    print("\nComparing global attributes:")
    attr_sets = [set(ds.attrs.keys()) for ds in datasets]
    all_attrs = set.union(*attr_sets)
    common_attrs = print_presence_and_missing(attr_sets, "Global attributes", all_attrs)
    for attr in common_attrs:
        values = [ds.attrs[attr] for ds in datasets]
        compare_values_across_files(values, f"Attribute '{attr}'")

    # Compare variable attributes
    print("\nComparing variable attributes for common variables:")
    for var in common_vars:
        var_attr_sets = [set(ds[var].attrs.keys()) for ds in datasets]
        all_var_attrs = set.union(*var_attr_sets)
        common_var_attrs = print_presence_and_missing(var_attr_sets, f"Attributes for variable '{var}'", all_var_attrs)
        for attr in common_var_attrs:
            values = [ds[var].attrs[attr] for ds in datasets]
            compare_values_across_files(values, f"  Attribute '{attr}'", indent="  ")

    # Compare data for common variables
    print("\nComparing data for common variables:")
    for var in common_vars:
        arrays = [ds[var].values for ds in datasets]
        all_equal = all(np.array_equal(arrays[0], arr) for arr in arrays[1:])
        if all_equal:
            print(f"Variable '{var}': Identical data.")
        else:
            print(f"Variable '{var}': Data differs between files.")
            for i, arr in enumerate(arrays[1:], start=2):
                if not np.array_equal(arrays[0], arr):
                    diff = np.where(arr != arrays[0])
                    print(f"File {i} differs at indices {diff} with values {arr[diff]}")

if len(netcdfs) > 1:
    compare_datasets(netcdfs)
else:
    print("Not enough datasets loaded for comparison.")


