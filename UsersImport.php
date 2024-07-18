<?php
namespace App\Imports;

use App\Models\User;
use App\Models\Datapribadi;
use App\Models\Identitasdanalamat;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Debug
        // dd($row);

        // Parsing tanggal_lahir menggunakan Date::excelToDateTimeObject
        $tanggal_lahir = Date::excelToDateTimeObject($row['Tanggal Lahir'])->format('Y-m-d');

        // Mengubah masa berlaku identitas jika diperlukan
        if (strtolower($row['Masa Berlaku Identitas']) === "seumur hidup") {
            $masa_berlaku_identitas = 'seumur hidup';
        } else {
            $masa_berlaku_identitas = Date::excelToDateTimeObject($row['Masa Berlaku Identitas'])->format('Y-m-d');
        }

        // Cari user berdasarkan email
        $user = User::where('email_karyawan', $row['Email Karyawan'])->first();

        if ($user) {
            // Jika user ditemukan, perbarui informasi tanpa mengubah password
            $user->update([
                'nama_karyawan' => $row['Nama Karyawan'],
                'status' => $row['Status'],
                'updated_at' => now(),
                'role_id' => $row['role_id'],
            ]);

            // Update data pribadi
            $datapribadi = Datapribadi::where('user_id', $user->id)->first();
            $datapribadi->update([
                'no_handphone' => $row['No Handphone'],
                'no_telepon' => $row['No Telepon'],
                'tempat_lahir' => $row['Tempat Lahir'],
                'tanggal_lahir' => $tanggal_lahir,
                'jk' => $row['Jenis Kelamin'],
                'status_pernikahan' => $row['Status Pernikahan'],
                'golongan_darah' => $row['Golongan Darah'],
                'agama' => $row['Agama']
            ]);

            Identitasdanalamat::where('datapribadi_id', $datapribadi->id)->update([
                'jenis_identitas' => $row['Jenis Identitas'],
                'no_identitas' => $row['No Identitas'],
                'masa_berlaku_identitas' => $masa_berlaku_identitas,
                'kode_pos' => $row['Kode Pos'],
                'alamat_identitas' => $row['Alamat Identitas'],
                'alamat_tinggal_sekarang' => $row['Alamat Tinggal Sekarang'],
            ]);
        } else {
            // Jika user tidak ditemukan, buat user baru dengan password default yang di-hash
            $user = User::create([
                'email_karyawan' => $row['Email Karyawan'],
                'nama_karyawan' => $row['Nama Karyawan'],
                'password' => Hash::make('medsa#karyawan312'),
                'status' => $row['Status'],
                'created_at' => now(),
                'updated_at' => now(),
                'role_id' => $row['role_id'],
            ]);

            // Buat data pribadi baru
            $datapribadi = Datapribadi::create([
                'user_id' => $user->id,
                'no_handphone' => $row['No Handphone'],
                'no_telepon' => $row['No Telepon'],
                'tempat_lahir' => $row['Tempat Lahir'],
                'tanggal_lahir' => $tanggal_lahir,
                'jk' => $row['Jenis Kelamin'],
                'status_pernikahan' => $row['Status Pernikahan'],
                'golongan_darah' => $row['Golongan Darah'],
                'agama' => $row['Agama'],
            ]);

            // Buat identitas dan alamat baru
            Identitasdanalamat::create([
                'datapribadi_id' => $datapribadi->id,
                'jenis_identitas' => $row['Jenis Identitas'],
                'no_identitas' => $row['No Identitas'],
                'masa_berlaku_identitas' => $masa_berlaku_identitas,
                'kode_pos' => $row['Kode Pos'],
                'alamat_identitas' => $row['Alamat Identitas'],
                'alamat_tinggal_sekarang' => $row['Alamat Tinggal Sekarang'],
            ]);
        }

        return $user;
    }
}
