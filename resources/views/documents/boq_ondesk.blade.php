<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BOQ On Desk</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }

        /* ================= HEADER ================= */
        .header-table {
            border: none;
            margin-bottom: 14px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            width: 90px;
        }

        .header-text {
            text-align: center;
        }

        .header-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header-sub {
            font-size: 11px;
            margin-bottom: 2px;
        }

        /* ================= TABLE ================= */
        .table-header {
            background: #e6ebf5;
            font-weight: bold;
            text-align: center;
        }

        /* ================= SECTION ================= */
        .section-title {
            background: #f4b400;
            font-weight: bold;
            text-align: center;
        }

        .section-content {
            padding: 10px;
            min-height: 45px;
        }

        /* ================= PEMOHON ================= */
        .pemohon-box {
            width: 240px;
            border: 1px solid #000;
            margin-top: 40px;
            padding: 10px;
        }

        .pemohon-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 40px;
        }

        .pemohon-role {
            text-align: center;
            font-weight: bold;
        }

        .pemohon-name {
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>

<body>

{{-- ================= HEADER ================= --}}
<table class="header-table">
    <tr>
        <td width="110">
            <img
                src="{{ public_path('images/supercorridor.png') }}"
                class="logo"
            >
        </td>
        <td>
            <div class="header-text">
                <div class="header-title">
                    KELENGKAPAN DOKUMEN BOQ ONDESK
                </div>
                <div class="header-sub bold">
                    {{ $nomorSurat ?? '-' }}
                </div>
                <div class="header-sub">
                    {{ $project->name ?? '-' }}
                </div>
                <div class="header-sub">
                    {{ $mitra ?? '-' }}
                </div>
            </div>
        </td>
        <td width="110"></td>
    </tr>
</table>

{{-- ================= TABEL KELENGKAPAN ================= --}}
<table>
    <tr class="table-header">
        <th rowspan="2">NO</th>
        <th rowspan="2">PIC</th>
        <th rowspan="2">ITEM</th>
        <th colspan="2">STATUS</th>
        <th rowspan="2">TANGGAL<br>DIBUAT</th>
        <th colspan="2">TANGGAL SUBMIT</th>
        <th rowspan="2">KETERANGAN</th>
    </tr>
    <tr class="table-header">
        <th>ADA</th>
        <th>TIDAK ADA</th>
        <th>TARGET</th>
        <th>ACTUAL</th>
    </tr>

    <tr>
        <td class="center">1</td>
        <td class="center">PLANNING</td>
        <td>BOQ ONDESK</td>
        <td></td><td></td><td></td><td></td><td></td><td></td>
    </tr>
    <tr>
        <td class="center">2</td>
        <td class="center">PLANNING</td>
        <td>KMZ</td>
        <td></td><td></td><td></td><td></td><td></td><td></td>
    </tr>
    <tr>
        <td class="center">3</td>
        <td class="center">PLANNING</td>
        <td>SKEMATIK</td>
        <td></td><td></td><td></td><td></td><td></td><td></td>
    </tr>
    <tr>
        <td class="center">4</td>
        <td class="center">SALES</td>
        <td>BEP PROYEKSI / PO</td>
        <td></td><td></td><td></td><td></td><td></td><td></td>
    </tr>
</table>

<br>

{{-- ================= LATAR BELAKANG ================= --}}
<table>
    <tr>
        <td class="section-title">LATAR BELAKANG</td>
    </tr>
    <tr>
        <td class="section-content">
            {{ $background ?? '-' }}
        </td>
    </tr>
</table>

<br>

{{-- ================= TUJUAN ================= --}}
<table>
    <tr>
        <td class="section-title">TUJUAN</td>
    </tr>
    <tr>
        <td class="section-content">
            {{ $objective ?? '-' }}
        </td>
    </tr>
</table>

<br>

{{-- ================= MANFAAT ================= --}}
<table>
    <tr>
        <td class="section-title">MANFAAT</td>
    </tr>
    <tr>
        <td class="section-content">
            {!! nl2br(e($benefit ?? '-')) !!}
        </td>
    </tr>
</table>

<br>

<p>
    Demikian justifikasi kebutuhan ini dibuat agar dapat digunakan sebagaimana mestinya.
    <br>
    Atas perhatian kami ucapkan terima kasih.
</p>

{{-- ================= PEMOHON ================= --}}
<div class="pemohon-box">
    <div class="pemohon-title">PEMOHON</div>
    <div class="pemohon-role">{{ $pemohonJabatan ?? '-' }}</div>
    <div class="pemohon-name">{{ $pemohonNama ?? '-' }}</div>
</div>

</body>
</html>
