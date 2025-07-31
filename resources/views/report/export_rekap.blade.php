<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Rekap Bulanan</title>
  <style type="text/css">
    html {
      font-family: "Arial", sans-serif;
      margin: 0;
      padding: 0;
    }
    .header {
      background-color: #d3eafc;
      padding: 40px 60px;
    }
    .body {
      padding: 40px 60px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ddd;
    }
    th {
      background-color: #f1f1f1;
      font-weight: bold;
      text-transform: uppercase;
      text-align: left;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-success { color: green; }
    .text-danger { color: red; }
    .text-primary { color: #007bff; }
    .font-bold { font-weight: bold; }
    .img-td {
      width: 60px;
    }
    .img-td img {
      width: 3rem;
    }
    .title {
      font-size: 20px;
      font-weight: bold;
      color: #2a4df1;
      text-align: right;
    }
    .subtext {
      font-size: 10px;
      color: #666;
    }
    .mb-2 { margin-bottom: 12px; }
    .mt-2 { margin-top: 10px; }
    .bg-total { background-color: #f9f9f9; font-weight: bold; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <table style="border: none; border-collapse: collapse;">
      <tr>
        <td class="img-td text-left" style="border: none;">
          <img src="{{ public_path('icons/zicma.png') }}">
        </td>
        <td class="text-left" style="border: none;">
          <p class="font-bold">{{ $market->nama_toko }}</p>
          <p class="subtext">{{ $market->alamat }}</p>
          <p class="subtext">{{ $market->no_telp }}</p>
        </td>
        <td class="title" style="border: none;">
          LAPORAN REKAP BULANAN
          <br>
          <span class="subtext">{{ \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y') }}</span>
        </td>
      </tr>
    </table>
  </div>

  <!-- Body -->
  <div class="body">

    <!-- Ringkasan -->
    <table class="mb-2">
      <tr>
        <th>Total Pemasukan</th>
        <th>Total Pengeluaran</th>
        <th>Saldo</th>
      </tr>
      <tr>
        <td class="text-success">Rp. {{ number_format($total_pemasukan, 0, ',', '.') }}</td>
        <td class="text-danger">Rp. {{ number_format($total_pengeluaran, 0, ',', '.') }}</td>
        <td class="text-primary">Rp. {{ number_format($selisih, 0, ',', '.') }}</td>
      </tr>
    </table>

    <!-- Tabel Rekap -->
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Keterangan</th>
          <th>Masuk</th>
          <th>Keluar</th>
          <th>Saldo</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rekap as $i => $data)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ \Carbon\Carbon::parse($data->tanggal)->format('d-m-Y') }}</td>
          <td>{{ $data->keterangan }}</td>
          <td class="text-success">
            {{ $data->masuk ? 'Rp. ' . number_format($data->masuk, 0, ',', '.') : '-' }}
          </td>
          <td class="text-danger">
            {{ $data->keluar ? 'Rp. ' . number_format($data->keluar, 0, ',', '.') : '-' }}
          </td>
          <td class="text-primary">
            Rp. {{ number_format($data->saldo, 0, ',', '.') }}
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center">Tidak ada data untuk bulan ini</td>
        </tr>
        @endforelse

        <!-- Jumlah Akhir -->
        <tr class="bg-total">
          <td colspan="3" class="text-center">Jumlah</td>
          <td class="text-success">Rp. {{ number_format($total_pemasukan, 0, ',', '.') }}</td>
          <td class="text-danger">Rp. {{ number_format($total_pengeluaran, 0, ',', '.') }}</td>
          <td class="text-primary">Rp. {{ number_format($selisih, 0, ',', '.') }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>
