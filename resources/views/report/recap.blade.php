@extends('templates/main')

@section('content')
<div class="row page-title-header">
  <div class="col-12">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h4 class="page-title">Rekap Laporan Bulanan</h4>
      <div class="print-btn-group">
        <div class="input-group">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <i class="mdi mdi-export print-icon"></i>
            </div>
            <button class="btn btn-print" type="button" data-toggle="modal" data-target="#cetakModalRekap">Export Laporan</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Grafik Statistik -->
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm" style="border-radius: 10px;">
      <div class="card-body" style="border-radius: 10px;">
        <h5 class="mb-4">Statistik Pemasukan dan Pengeluaran</h5>
        <canvas id="rekapChart" height="100"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Ringkasan Total -->
<div class="row mt-4">
  <div class="col-md-4">
    <div class="card shadow-sm" style="border-radius: 10px;">
      <div class="card-body text-center" style="border-radius: 10px;">
        <h6>Total Pemasukan</h6>
        <h4 class="text-success">Rp. {{ number_format($total_pemasukan, 0, ',', '.') }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm" style="border-radius: 10px;">
      <div class="card-body text-center" style="border-radius: 10px;">
        <h6>Total Pengeluaran</h6>
        <h4 class="text-danger">Rp. {{ number_format($total_pengeluaran, 0, ',', '.') }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm" style="border-radius: 10px;">
      <div class="card-body text-center" style="border-radius: 10px;">
        <h6>Saldo</h6>
        <h4 class="text-primary">Rp. {{ number_format($selisih, 0, ',', '.') }}</h4>
      </div>
    </div>
  </div>
</div>

<!-- Tabel Rekap -->
<div class="row mt-4 mb-5">
  <div class="col-12">
    <div class="card shadow-sm" style="border-radius: 10px;">
      <div class="card-body" style="border-radius: 10px;">
        <h5 class="mb-3">Detail Rekap Laporan</h5>
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead class="thead-light">
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
              @forelse ($rekap as $i => $row)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
                  <td>{{ $row->keterangan }}</td>
                  <td class="text-success">
                    {{ $row->masuk ? 'Rp. ' . number_format($row->masuk, 0, ',', '.') : '-' }}
                  </td>
                  <td class="text-danger">
                    {{ $row->keluar ? 'Rp. ' . number_format($row->keluar, 0, ',', '.') : '-' }}
                  </td>
                  <td class="text-primary">
                    {{ 'Rp. ' . number_format($row->saldo, 0, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center">Tidak ada data transaksi bulan ini</td>
                </tr>
              @endforelse
              {{-- Total Baris Jumlah --}}
              <tr class="font-weight-bold bg-light">
                <td colspan="3" class="text-center">Jumlah</td>
                <td class="text-success">Rp. {{ number_format($total_pemasukan, 0, ',', '.') }}</td>
                <td class="text-danger">Rp. {{ number_format($total_pengeluaran, 0, ',', '.') }}</td>
                <td class="text-primary">Rp. {{ number_format($selisih, 0, ',', '.') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row modal-group">
  <div class="modal fade" id="cetakModalRekap" tabindex="-1" role="dialog" aria-labelledby="cetakModalRekapLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content rounded shadow-sm border-0">
        <form action="{{ url('/report/rekap/export') }}" method="POST" target="_blank">
          @csrf
          <div class="modal-header bg-light border-bottom-0">
            <h5 class="modal-title font-weight-bold" id="cetakModalRekapLabel">Export Rekap Laporan</h5>
            <button type="button" class="close close-btn" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
            </button>
          </div>
          <div class="modal-body pt-3 pb-1">
            <div class="row justify-content-center">
              <div class="col-10">
                <div class="form-group">
                  <label class="font-weight-bold mb-2">Pilih Bulan Rekap</label>
                  <input type="month" class="form-control form-control-lg rounded-pill shadow-sm" name="bulan" value="{{ now()->format('Y-m') }}">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 pb-4 pt-2 d-flex justify-content-center">
            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
              <i class="mdi mdi-file-export mr-1"></i> Export PDF
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
var ctx = document.getElementById('rekapChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Pemasukan', 'Pengeluaran'],
        datasets: [{
            label: 'Jumlah',
            data: [{{ $total_pemasukan }}, {{ $total_pengeluaran }}],
            backgroundColor: ['#1cc88a', '#e74a3b'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    suggestedMax: 5000000,
                    callback: function(value) {
                        return 'Rp. ' + value.toLocaleString('id-ID');
                    }
                },
                gridLines: {
                    drawOnChartArea: true
                }
            }]
        },
        legend: { display: false },
        tooltips: {
            callbacks: {
                label: function(tooltipItem) {
                    return 'Rp. ' + tooltipItem.yLabel.toLocaleString('id-ID');
                }
            }
        }
    }
});
</script>
@endsection
