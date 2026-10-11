<?php
$nama = trim($_POST['nama'] ?? '');
$nim = trim($_POST['nim'] ?? '');
$hasil = false;
$dataSemester = [];
$totalSKS = 0;
$totalMutu = 0;
$totalMatkul = 0;
$ipk = 0.0;
$predikat = 'Belum ada data';
$postedSemesters = $_POST['semester'] ?? [];
if (strpos((string)($_POST['action'] ?? ''), 'delete_course:') === 0) {
    $parts = explode(':', (string)$_POST['action']); $ds = (int)($parts[1] ?? 0); $di = (int)($parts[2] ?? -1);
    if ($ds >= 1 && $ds <= 6 && $di >= 0 && isset($postedSemesters[$ds])) {
        foreach (['matkul','sks','nilai','angka'] as $key) { if (isset($postedSemesters[$ds][$key][$di])) array_splice($postedSemesters[$ds][$key], $di, 1); }
    }
}
$activeSemester = (int)($_POST['semester_tampilan'] ?? 1);
if ($activeSemester < 1 || $activeSemester > 6) $activeSemester = 1;


function getBobotNilai($nilai)
{
    switch ($nilai) {
        case 'A':
            return 4.00;
        case 'A-':
            return 3.75;
        case 'B':
            return 3.00;
        case 'B-':
            return 2.75;
        case 'C':
            return 2.00;
        case 'D':
            return 1.00;
        default:
            return 0.00;
    }
}

function getPredikat($ipk) {
    if ($ipk >= 3.50) return 'Sangat Memuaskan';
    elseif ($ipk >= 3.00) return 'Memuaskan';
    elseif ($ipk >= 2.00) return 'Cukup';
    return 'Perlu Perbaikan';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'calculate') !== 'add_course' && strpos((string)($_POST['action'] ?? ''), 'delete_course:') !== 0) {
    $semesters = $_POST['semester'] ?? [];

    for ($semester = 1; $semester <= 6; $semester++) {
        $dataSemester[$semester] = ['matkul' => [], 'total_sks' => 0, 'total_mutu' => 0, 'ips' => 0];
        if (!isset($semesters[$semester])) continue;

        $matkulList = $semesters[$semester]['matkul'] ?? [];
        $sksList = $semesters[$semester]['sks'] ?? [];
        $nilaiList = $semesters[$semester]['nilai'] ?? [];
        $angkaList = $semesters[$semester]['angka'] ?? [];
        $jumlahBaris = max(count($matkulList), count($sksList), count($nilaiList), count($angkaList));

        for ($i = 0; $i < $jumlahBaris; $i++) {
            $matkul = trim($matkulList[$i] ?? '');
            $sksInput = $sksList[$i] ?? '';
            $nilai = trim($nilaiList[$i] ?? '');
            $angkaInput = trim((string)($angkaList[$i] ?? ''));
            $sks = filter_var($sksInput, FILTER_VALIDATE_INT);

            // Nilai huruf dan angka sama-sama opsional. Jika angka diisi, angka digunakan.
            if ($angkaInput !== '') {
                $angkaNormalized = str_replace(',', '.', $angkaInput);
                if (!is_numeric($angkaNormalized)) continue;
                $bobot = (float)$angkaNormalized;
                if ($bobot < 0 || $bobot > 4) continue;
            } elseif ($nilai !== '') {
                if (!in_array($nilai, ['A', 'A-', 'B', 'B-', 'C', 'D'], true)) continue;
                $bobot = getBobotNilai($nilai);
            } else {
                continue;
            }

            // Baris kosong atau tidak lengkap/tidak valid tidak dihitung.
            if ($matkul === '' || $sks === false || $sks < 1 || $sks > 6) continue;
            $tampilanNilai = $angkaInput !== '' ? number_format($bobot, 2) : $nilai;
            if ($angkaInput !== '' && $nilai !== '') $tampilanNilai .= ' (' . $nilai . ')';
            $mutu = $sks * $bobot;
            $dataSemester[$semester]['matkul'][] = ['nama' => $matkul, 'sks' => $sks, 'nilai' => $tampilanNilai, 'bobot' => $bobot, 'mutu' => $mutu];
            $dataSemester[$semester]['total_sks'] += $sks;
            $dataSemester[$semester]['total_mutu'] += $mutu;
            $totalSKS += $sks;
            $totalMutu += $mutu;
            $totalMatkul++;
        }
        if ($dataSemester[$semester]['total_sks'] > 0) {
            $dataSemester[$semester]['ips'] = $dataSemester[$semester]['total_mutu'] / $dataSemester[$semester]['total_sks'];
        }
    }

    if ($totalSKS > 0) {
        $ipk = $totalMutu / $totalSKS;
        $predikat = getPredikat($ipk);
    }
    $hasil = true;
}
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SMART ACADEMIC CALCULATOR</title>
<style>
:root{--ink:#F2D0B5;--muted:#E0B487;--line:rgba(224,180,135,.38);--glass:rgba(50,29,29,.88);--blue:#E0B487;--blue2:#A33335;--green:#E0B487;--peach:#F2D0B5;--brick:#A33335;--dark:#321D1D;--brown:#8D7054}
*{box-sizing:border-box}html{scroll-behavior:auto}body{margin:0;min-height:100vh;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--ink);background:#091326}
.video-bg{position:fixed;inset:0;width:100%;height:100%;object-fit:cover;z-index:-3}.video-shade{position:fixed;inset:0;background:linear-gradient(100deg,rgba(3,9,24,.78),rgba(5,12,28,.55));z-index:-2}
.page{width:min(920px,100%);margin:auto;padding:24px;position:relative;z-index:2}.topbar{display:flex;align-items:center;justify-content:center;gap:15px;margin-bottom:28px;text-align:center}.brand{display:flex;align-items:center;justify-content:center;width:100%}.brand-title{width:100%;text-align:center}.brand h1{font-size:clamp(22px,3vw,36px);letter-spacing:1.2px;margin:0;font-weight:800}.brand p{margin:8px 0 0;color:var(--muted);font-size:13px}.pill{display:none}
.layout{display:grid;grid-template-columns:minmax(0,860px);justify-content:center;align-items:start;gap:22px}.left-column,.right-column{min-width:0}.panel{background:var(--glass);border:1px solid var(--line);border-radius:20px;padding:22px;box-shadow:0 16px 45px rgba(0,0,0,.2);backdrop-filter:blur(14px);margin-bottom:18px}.panel-title{display:flex;align-items:center;gap:10px;margin:0 0 17px;font-size:18px}.panel-title span{font-size:22px}.hint{color:var(--muted);font-size:13px;line-height:1.55;margin:-7px 0 18px}.field-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.field label,.course-head{display:block;font-size:12px;color:#d8e5fb;margin-bottom:7px;font-weight:700;letter-spacing:.2px}.field input,.course-row input,.course-row select,.semester-picker{width:100%;min-width:0;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:11px 12px;color:#fff;outline:none;font-size:14px}.field input::placeholder,.course-row input::placeholder{color:#bac7dc}.field input:focus,.course-row input:focus,.course-row select:focus,.semester-picker:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(139,184,255,.12)}select option{color:#172033;background:#fff}.semester-picker{margin-bottom:17px}.semester-box{display:none;margin-top:24px;padding-top:18px;border-top:1px solid rgba(224,180,135,.28)}
.semester-selector{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:18px 0 12px}
.semester-selector input{position:absolute;opacity:0;pointer-events:none}
.semester-selector label{display:inline-block;padding:10px 16px;border:1px solid rgba(224,180,135,.5);border-radius:10px;background:rgba(50,29,29,.24);color:var(--peach);cursor:pointer;font-weight:700;transition:background .2s,color .2s}
.semester-selector input:checked + label{background:var(--peach);color:var(--dark);border-color:var(--peach)}
.semester-selector input:focus-visible + label{outline:2px solid var(--peach);outline-offset:3px}
.semester-selector:has(#sem1:checked) ~ #semester-1,.semester-selector:has(#sem2:checked) ~ #semester-2,.semester-selector:has(#sem3:checked) ~ #semester-3,.semester-selector:has(#sem4:checked) ~ #semester-4,.semester-selector:has(#sem5:checked) ~ #semester-5,.semester-selector:has(#sem6:checked) ~ #semester-6{display:block}@keyframes appear{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}.semester-heading{display:flex;align-items:center;justify-content:space-between;margin:0 0 12px}.semester-heading h3{margin:0;font-size:16px}.semester-heading small{color:#c7d7f2}.course-head,.course-row{display:grid;grid-template-columns:minmax(0,1fr) 58px 88px 105px;gap:7px;align-items:center}.course-head{margin-bottom:7px}.course-row{margin-bottom:8px}.course-container .extra-course-row{display:grid}.add-course-button{margin-top:10px}
.row-toggle{position:absolute;opacity:0;pointer-events:none}
.row-add-label{display:inline-block;margin:2px 0 10px;padding:8px 12px;border:1px solid rgba(224,180,135,.45);border-radius:9px;background:rgba(50,29,29,.18);color:var(--peach);cursor:pointer;font-size:12px;font-weight:700}
.row-toggle:focus-visible + .row-add-label{outline:2px solid var(--peach);outline-offset:2px}
.row-toggle:checked + .row-add-label{display:none}
.row-toggle:checked + .row-add-label + .course-row{display:grid}
.course-row input,.course-row select{padding:10px 8px;font-size:13px}.course-name-cell{display:flex;align-items:center;gap:6px;min-width:0}.course-name-cell input{flex:1;min-width:0}.delete{flex:0 0 34px;width:34px;height:36px;border:1px solid rgba(255,130,150,.35);border-radius:9px;background:rgba(255,80,105,.13);color:#ffb8c3;font-size:19px;cursor:pointer}.actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:12px}.btn{border:0;border-radius:11px;padding:11px 14px;font-weight:700;cursor:pointer;transition:transform .18s,filter .18s}.btn:hover{transform:translateY(-1px);filter:brightness(1.08)}.btn-add{background:rgba(110,231,183,.15);border:1px solid rgba(110,231,183,.35);color:#a7f3d0}.btn-submit{width:100%;margin-top:18px;padding:14px;background:linear-gradient(110deg,#5d83ff,#8a6cff);color:#fff;font-size:15px}.btn-reset{background:rgba(255,255,255,.1);border:1px solid var(--line);color:#e6eeff}.empty-state{padding:25px 15px;text-align:center;border:1px dashed var(--line);border-radius:13px;color:#d5e0f2;font-size:13px;line-height:1.7}.student-strip{padding:14px;border-radius:13px;background:rgba(255,255,255,.07);margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px}.student-strip small{display:block;color:#b9c8df;font-size:11px;margin-bottom:5px}.student-strip strong{overflow-wrap:anywhere;font-size:14px}.metric-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:17px}.metric{padding:15px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.06)}.metric small{display:block;color:#c5d2e8;font-size:11px;margin-bottom:7px}.metric strong{font-size:25px;letter-spacing:-.5px}.metric.primary{background:linear-gradient(135deg,rgba(75,112,255,.38),rgba(124,94,255,.25));border-color:rgba(158,176,255,.4)}.metric.primary strong{color:#c9d7ff}.semester-result{padding:14px;border:1px solid var(--line);border-radius:13px;margin-bottom:11px;background:rgba(255,255,255,.045)}.semester-result-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:9px}.semester-result h3{margin:0;font-size:14px}.ips-badge{background:rgba(110,231,183,.13);color:#a7f3d0;border:1px solid rgba(110,231,183,.25);border-radius:9px;padding:6px 9px;font-size:12px;font-weight:800;white-space:nowrap}.mini-meta{color:#c5d2e8;font-size:12px;margin-bottom:10px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:12px;min-width:390px}th,td{text-align:left;padding:9px 7px;border-bottom:1px solid rgba(255,255,255,.1)}th{color:#c5d7fa;font-size:11px;font-weight:700}td{color:#f2f6ff}.predikat{margin-top:15px;border-radius:13px;padding:14px;background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25);display:flex;justify-content:space-between;align-items:center;gap:10px}.predikat span{font-size:12px;color:#d3f9e9}.predikat strong{color:#a7f3d0}.footnote{color:#c5d2e8;font-size:11px;line-height:1.6;margin-top:13px}.sticker{position:fixed;right:14px;bottom:8px;width:150px;opacity:.9;pointer-events:none;z-index:1;filter:drop-shadow(0 5px 12px rgba(0,0,0,.3))}.sticker img{display:block;width:100%;height:auto;mix-blend-mode:screen}.footer{text-align:center;color:var(--peach);padding:26px 10px 22px;line-height:1.8}.watermark-name{font-size:22px;font-weight:800;letter-spacing:.3px;color:var(--peach)}.watermark-tagline{font-size:9px;letter-spacing:3px;color:var(--muted);margin-top:2px}.watermark-copyright{font-size:10px;color:var(--muted);margin-top:10px}
@media(max-width:900px){.layout{grid-template-columns:1fr}.right-column{order:2}.left-column{order:1}.page{padding:15px}.panel{padding:17px}.sticker{width:100px;opacity:.7}}@media(max-width:440px){.topbar{align-items:flex-start}.pill{display:none}.field-grid{grid-template-columns:1fr}.course-head,.course-row{grid-template-columns:minmax(155px,1fr) 42px 66px 78px;gap:5px}.course-row input,.course-row select{padding:9px 5px;font-size:12px}.course-container{overflow-x:auto}.delete{flex-basis:29px;width:29px;height:33px;padding:0}.metric strong{font-size:22px}.panel-title{font-size:16px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;scroll-behavior:auto!important;transition:none!important}}


body{color:var(--peach)}
.video-shade{background:linear-gradient(100deg,rgba(50,29,29,.60),rgba(50,29,29,.36))}
.brand-icon{background:rgba(163,51,53,.78);border-color:var(--brown)}
.brand h1{color:var(--peach)}
.brand p,.pill{color:var(--peach)}
.pill{background:rgba(50,29,29,.72);border-color:var(--brown)}
.panel{background:rgba(50,29,29,.30);border-color:rgba(224,180,135,.36)}
.panel-title,.semester-heading h3{color:var(--peach)}
.hint,.field label,.course-head,.semester-heading small,.footnote{color:var(--peach)}
.hint{background:transparent;border:0;box-shadow:none}
.field input,.course-row input,.course-row select,.semester-picker{background:var(--peach);color:var(--dark);border:1px solid var(--brown)}
.field input::placeholder,.course-row input::placeholder{color:#765A48;opacity:1}
.field input:focus,.course-row input:focus,.course-row select:focus,.semester-picker:focus{border-color:var(--brick);box-shadow:0 0 0 3px rgba(224,180,135,.28)}
select option{color:var(--dark);background:var(--peach)}
.btn-add{background:var(--peach);border:1px solid var(--brown);color:var(--dark)}
.delete{background:var(--brick);border-color:var(--brick);color:#fff}
.btn-submit{background:linear-gradient(110deg,var(--brick),var(--brown));color:#fff}
.btn-reset{background:var(--brown);border:1px solid var(--peach);color:#fff}
.empty-state{border-color:var(--brown);color:var(--peach)}
.student-strip{background:rgba(141,112,84,.28);border:1px solid rgba(224,180,135,.28)}
.student-strip small,.metric small,.mini-meta{color:var(--peach)}
.metric{background:rgba(141,112,84,.24);border-color:rgba(224,180,135,.32)}
.metric.primary{background:linear-gradient(135deg,rgba(163,51,53,.92),rgba(50,29,29,.94));border-color:var(--peach)}
.metric.primary strong{color:var(--peach)}
.semester-result{background:rgba(50,29,29,.34);border-color:rgba(224,180,135,.30)}
.semester-result h3{color:var(--peach)}
.ips-badge{background:rgba(224,180,135,.18);color:var(--peach);border-color:var(--brown)}
th{color:#fff;background:rgba(163,51,53,.75)}
th,td{border-bottom-color:rgba(224,180,135,.25)}
td{color:var(--peach)}
.predikat{background:rgba(141,112,84,.28);border-color:var(--brown)}
.predikat span,.predikat strong{color:var(--peach)}
.footer{color:var(--peach)}
@media print{.video-bg,.video-shade,.sticker{display:none!important}body{background:#fff;color:#321D1D}.panel{background:#fff;color:#321D1D;border:1px solid #8D7054;box-shadow:none}.brand p,.hint,.footnote,.mini-meta,td,.panel-title,.semester-result h3{color:#321D1D}}

</style>
</head>
<body>
<video class="video-bg" autoplay muted loop playsinline aria-hidden="true"><source src="hutaobg.mp4" type="video/mp4"></video>
<div class="video-shade"></div>
<div class="sticker" aria-hidden="true"><img src="stiker.gif" alt=""></div>
<main class="page">
  <header class="topbar">
    <div class="brand">
      <div class="brand-title">
        <h1>SMART ACADEMIC CALCULATOR</h1>
        <p>Rekap Nilai Akademik Mahasiswa · Semester 1–6</p>
      </div>
    </div>
  </header>
  <div class="layout">
    <section class="left-column">
      <form method="POST" id="formNilai">
        
        <section class="panel">
          <h2 class="panel-title">Data Mahasiswa</h2>
          <div class="field-grid">
            <div class="field"><label for="nama">Nama mahasiswa</label><input id="nama" name="nama" type="text" placeholder="Masukkan nama (opsional)" value="<?= e($nama) ?>"></div>
            <div class="field"><label for="nim">NIM</label><input id="nim" name="nim" type="text" placeholder="Masukkan NIM (opsional)" value="<?= e($nim) ?>"></div>
          </div>
        </section>
        <section class="panel">
          <h2 class="panel-title">Input Nilai Mata Kuliah</h2>
          <p class="hint">Pilih semester, lalu isi nama mata kuliah dan SKS. Nilai huruf dan nilai angka sama-sama opsional. Isi salah satu saja atau keduanya; jika keduanya diisi, nilai angka yang digunakan. Nilai angka diisi 0–4,00. Semester yang belum diisi boleh dikosongkan.</p>
          <div class="semester-selector" role="radiogroup" aria-label="Pilih semester">
            <?php for ($s=1;$s<=6;$s++): ?>
              <input type="radio" name="semester_tampilan" id="sem<?= $s ?>" value="<?= $s ?>" <?= $s===$activeSemester?'checked':'' ?>>
              <label for="sem<?= $s ?>">Semester <?= $s ?></label>
            <?php endfor; ?>
          </div>
          <?php for ($s=1;$s<=6;$s++): ?>
          <div class="semester-box" id="semester-<?= $s ?>">
            <div class="semester-heading"><h3>Semester <?= $s ?></h3><small>Isi baris yang diperlukan</small></div>
            <div class="course-head"><span>Nama Mata Kuliah</span><span>SKS</span><span>Nilai Huruf (opsional)</span><span>Nilai Angka (opsional)</span></div>
            <div class="course-container" id="courses-<?= $s ?>">
              <?php
                $matkulValues = $postedSemesters[$s]['matkul'] ?? [''];
                $sksValues = $postedSemesters[$s]['sks'] ?? [''];
                $nilaiValues = $postedSemesters[$s]['nilai'] ?? [''];
                $angkaValues = $postedSemesters[$s]['angka'] ?? [''];
                $rowCount = max(1, count($matkulValues), count($sksValues), count($nilaiValues), count($angkaValues));
                if (($_POST['action'] ?? '') === 'add_course' && $activeSemester === $s) $rowCount++;
                if (strpos((string)($_POST['action'] ?? ''), 'delete_course:') === 0 && (int)(explode(':', (string)$_POST['action'])[1] ?? 0) === $s) $rowCount = max(1, $rowCount - 1);
                for ($i = 0; $i < $rowCount; $i++):
                  $matkulValue = $matkulValues[$i] ?? '';
                  $sksValue = $sksValues[$i] ?? '';
                  $nilaiValue = $nilaiValues[$i] ?? '';
                  $angkaValue = $angkaValues[$i] ?? '';
              ?>
                <div class="course-row">
                  <div class="course-name-cell">
                    <input name="semester[<?= $s ?>][matkul][]" type="text" placeholder="Nama mata kuliah" aria-label="Nama mata kuliah semester <?= $s ?>" value="<?= e($matkulValue) ?>">
                    <button type="submit" class="delete" name="action" value="delete_course:<?= $s ?>:<?= $i ?>" title="Hapus mata kuliah" aria-label="Hapus mata kuliah ini" formnovalidate>×</button>
                  </div>
                  <input name="semester[<?= $s ?>][sks][]" type="number" min="1" max="6" step="1" placeholder="SKS" aria-label="SKS semester <?= $s ?>" value="<?= e($sksValue) ?>">
                  <select name="semester[<?= $s ?>][nilai][]" aria-label="Nilai huruf semester <?= $s ?>">
                    <option value="" <?= $nilaiValue===''?'selected':'' ?>>Pilih</option>
                    <?php foreach (['A','A-','B','B-','C','D'] as $opsiNilai): ?>
                      <option value="<?= $opsiNilai ?>" <?= $nilaiValue===$opsiNilai?'selected':'' ?>><?= $opsiNilai ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input name="semester[<?= $s ?>][angka][]" type="number" min="0" max="4" step="0.01" placeholder="0,00–4,00" aria-label="Nilai angka semester <?= $s ?>" value="<?= e($angkaValue) ?>">
                </div>
              <?php endfor; ?>
            </div>
            <button type="submit" class="btn btn-add add-course-button" name="action" value="add_course">+ Tambah Mata Kuliah</button>
          </div>
          <?php endfor; ?>
          <button type="submit" class="btn btn-submit" name="action" value="calculate">Hitung IPS &amp; IPK</button>
        </section>
      </form>
    </section>

    <aside class="right-column" id="hasil">
      <section class="panel">
        <h2 class="panel-title">Hasil Perhitungan</h2>
        <?php if (!$hasil): ?>
          <div class="empty-state">Hasil perhitungan akan muncul di sini.<br><br>Isi data mata kuliah, lalu tekan <strong>Hitung IPS &amp; IPK</strong>.</div>
        <?php else: ?>
          <div class="student-strip"><div><small>Nama mahasiswa</small><strong><?= e($nama) ?></strong></div><div><small>NIM</small><strong><?= e($nim) ?></strong></div></div>
          <div class="metric-grid">
            <div class="metric"><small>Total mata kuliah</small><strong><?= $totalMatkul ?></strong></div>
            <div class="metric"><small>Total SKS</small><strong><?= $totalSKS ?></strong></div>
            <div class="metric primary"><small>IPK keseluruhan</small><strong><?= number_format($ipk,2) ?></strong></div>
            <div class="metric"><small>Predikat</small><strong style="font-size:16px"><?= e($predikat) ?></strong></div>
          </div>
          <?php $adaData=false; for($s=1;$s<=6;$s++): if(count($dataSemester[$s]['matkul'])===0) continue; $adaData=true; ?>
          <div class="semester-result">
            <div class="semester-result-head"><h3>Semester <?= $s ?></h3><span class="ips-badge">IPS <?= number_format($dataSemester[$s]['ips'],2) ?></span></div>
            <div class="mini-meta"><?= count($dataSemester[$s]['matkul']) ?> mata kuliah · <?= $dataSemester[$s]['total_sks'] ?> SKS</div>
            <div class="table-wrap"><table><thead><tr><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th><th>Bobot</th><th>Mutu</th></tr></thead><tbody>
              <?php foreach($dataSemester[$s]['matkul'] as $mk): ?><tr><td><?= e($mk['nama']) ?></td><td><?= $mk['sks'] ?></td><td><?= e($mk['nilai']) ?></td><td><?= number_format($mk['bobot'],2) ?></td><td><?= number_format($mk['mutu'],2) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
          </div>
          <?php endfor; if(!$adaData): ?><div class="empty-state">Belum ada mata kuliah lengkap yang bisa dihitung. Pastikan setiap baris memiliki nama mata kuliah, SKS, dan nilai.</div><?php endif; ?>
          <div class="predikat"><span>Rekap seluruh semester yang diisi</span><strong>IPK <?= number_format($ipk,2) ?></strong></div>
        <?php endif; ?>
        <div class="actions"><a class="btn btn-reset" href="<?= e($_SERVER['PHP_SELF']) ?>">Reset</a></div>
      </section>
    </aside>
  </div>
  <footer class="footer">
    <div class="watermark-name">difariesol.</div>
    <div class="watermark-tagline">CRAFTED WITH PRECISION</div>
    <div class="watermark-copyright">© 2026 Difariesol. All rights reserved.</div>
  </footer>
</main>
</body>
</html>