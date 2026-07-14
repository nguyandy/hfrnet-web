import xarray as xr
import s3fs


# Initialize S3 filesystem with anonymous access
s3 = s3fs.S3FileSystem(anon=True)
bucket = 'rps-nccf-hfrnet-dissemination-uat'
    

if __name__ == "__main__":
    # Example file path
    file_path = 'hfrtv/2025/07/averages/rtv-usegc-1km-mon-avg_v1r0_hfr_s202507010000000_e202507312359590_c202509010018469.nc'
    
    try:
        with s3.open(f's3://{bucket}/{file_path}', mode='rb') as f:
            ds = xr.open_dataset(f, engine='h5netcdf', cache=True)

            print(f"Successfully opened dataset: {ds}")
            
            # Perform any checks or operations on the dataset here
            # For example, checking dimensions or variables
            print("Dataset dimensions:", ds.dims)
            print("Dataset variables:", ds.data_vars)

            print("u_var:", ds['u_var'])

            # Select all non-zero and non-NaN u_var values
            valid_u = ds['u_var'].where((ds['u_var'] != 0) & (~ds['u_var'].isnull()), drop=True)            
            if valid_u.size == 0 or valid_u.values.size == 0:
                print("No non-zero, non-NaN values found.")
            else:
                print("Non-zero, non-NaN u_var values:")
                print(valid_u.values)

            # Check if all values are NaN
            all_nan = ds['u_var'].isnull().all().item()
            print(f"All values in u_var are NaN: {all_nan}")

    except Exception as e:
        print(f"Error opening file {file_path}: {e}")