<?php
require __DIR__ . '/../config/admin_guard.php';

$cfg = $pdo->query("SELECT * FROM ksk_undi_settings WHERE id=1")->fetch() ?: [];
$stt = statusUndian($pdo);

$jumlahLayak = (int) $pdo->query("SELECT COUNT(*) FROM staff WHERE status=2")->fetchColumn();
$jumlahUndi  = (int) $pdo->query("SELECT COUNT(*) FROM ksk_undi_voter_log")->fetchColumn();
$belum       = max(0, $jumlahLayak - $jumlahUndi);
$peratus     = $jumlahLayak > 0 ? round($jumlahUndi / $jumlahLayak * 100, 1) : 0.0;

$layakCaw = $pdo->query(
    "SELECT cawangan, COUNT(*) bil FROM staff WHERE status=2 GROUP BY cawangan ORDER BY cawangan"
)->fetchAll();
$undiCaw = [];
foreach ($pdo->query("SELECT cawangan, COUNT(*) bil FROM ksk_undi_voter_log GROUP BY cawangan")->fetchAll() as $r) {
    $undiCaw[$r['cawangan']] = (int)$r['bil'];
}

$jawatan = $pdo->query(
    "SELECT id, nama_jawatan, kategori FROM ksk_undi_positions ORDER BY susunan ASC"
)->fetchAll();
$qC = $pdo->prepare(
    "SELECT s.nama, s.cawangan, s.gred, COUNT(*) undi
       FROM ksk_undi_votes v JOIN staff s ON s.id=v.candidate_staff_id
      WHERE v.position_id=? GROUP BY v.candidate_staff_id
      ORDER BY undi DESC, s.nama ASC"
);

function kiraRank(array $rows): array {
    $out=[]; $rank=0; $idx=0; $prev=null;
    $maxUndi = $rows ? (int)$rows[0]['undi'] : 0;
    $bilMax  = 0; foreach ($rows as $r) if ((int)$r['undi']===$maxUndi) $bilMax++;
    $freq=[]; foreach ($rows as $r){ $u=(int)$r['undi']; $freq[$u]=($freq[$u]??0)+1; }
    foreach ($rows as $r) {
        $idx++; $u=(int)$r['undi'];
        if ($u!==$prev){ $rank=$idx; $prev=$u; }
        $out[]=['nama'=>$r['nama'],'cawangan'=>$r['cawangan'],'gred'=>$r['gred'],
                'undi'=>$u,'rank'=>$rank,'tie'=>$freq[$u]>1];
    }
    return ['calon'=>$out, 'seri'=>($bilMax>1)];
}

$dataA=[]; $dataB=[];
foreach ($jawatan as $j) {
    $qC->execute([(int)$j['id']]);
    $info = kiraRank($qC->fetchAll());
    $entry = ['nama'=>$j['nama_jawatan'],'seri'=>$info['seri'],'calon'=>$info['calon']];
    if ($j['kategori']==='A') $dataA[]=$entry; else $dataB[]=$entry;
}

function xesc($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_XML1, 'UTF-8'); }
function colL($n){ $s=''; while($n>0){ $m=($n-1)%26; $s=chr(65+$m).$s; $n=intdiv($n-$m,26);} return $s; }

function sheetXml(array $rows, array $cols, array $merges = [], array $rowHt = []): string {
    $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
       . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    if ($cols) {
        $x .= '<cols>';
        foreach ($cols as $i => $w) {
            $c = $i + 1;
            $x .= '<col min="'.$c.'" max="'.$c.'" width="'.$w.'" customWidth="1"/>';
        }
        $x .= '</cols>';
    }
    $x .= '<sheetData>';
    foreach ($rows as $ri => $row) {
        $r = $ri + 1;
        $ht = isset($rowHt[$r]) ? ' ht="'.$rowHt[$r].'" customHeight="1"' : '';
        $x .= '<row r="'.$r.'"'.$ht.'>';
        foreach ($row as $ci => $cell) {
            if ($cell === null) continue;
            $ref = colL($ci + 1) . $r;
            $s = isset($cell['s']) ? ' s="'.$cell['s'].'"' : '';
            $t = $cell['t'] ?? 's';
            if ($t === 'n') {
                $v = is_numeric($cell['v']) ? $cell['v'] : 0;
                $x .= '<c r="'.$ref.'"'.$s.'><v>'.$v.'</v></c>';
            } else {
                $x .= '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'
                    . xesc($cell['v'] ?? '') . '</t></is></c>';
            }
        }
        $x .= '</row>';
    }
    $x .= '</sheetData>';
    if ($merges) {
        $x .= '<mergeCells count="'.count($merges).'">';
        foreach ($merges as $m) $x .= '<mergeCell ref="'.$m.'"/>';
        $x .= '</mergeCells>';
    }
    $x .= '</worksheet>';
    return $x;
}

function C($v, $s = 0, $t = 's'){ return ['v'=>$v, 's'=>$s, 't'=>$t]; }
function N($v, $s = 0){ return ['v'=>$v, 's'=>$s, 't'=>'n']; }

$tajuk = $cfg['tajuk_undian'] ?? 'Undian KSK JPJ Negeri Pahang 2026';

$rows=[]; $merges=[]; $ht=[];
$rows[] = [C('LAPORAN KEPUTUSAN UNDIAN', 2)];
$merges[]='A1:D1'; $ht[1]=26;
$rows[] = [C($tajuk, 4)];
$merges[]='A2:D2';
$rows[] = [C('Dijana pada: '.date('d/m/Y H:i:s'), 0)];
$merges[]='A3:D3';
$rows[] = [null];

$rows[] = [C('MAKLUMAT UNDIAN',3),C('',3),C('',3),C('',3)]; $merges[]='A'.(count($rows)).':D'.(count($rows));
$rows[] = [C('Tarikh Mula',10), C($cfg['tarikh_mula']??'-',11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [C('Tarikh Tamat',10), C($cfg['tarikh_tamat']??'-',11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [C('Status Semasa',10), C($stt['buka']?'DIBUKA':'DITUTUP',11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [null];

$rows[] = [C('STATISTIK PENYERTAAN',3),C('',3),C('',3),C('',3)]; $merges[]='A'.count($rows).':D'.count($rows);
$rows[] = [C('Jumlah Kakitangan Layak',10), N($jumlahLayak,11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [C('Jumlah Telah Mengundi',10), N($jumlahUndi,11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [C('Belum Mengundi',10), N($belum,11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [C('Kadar Penyertaan',10), C($peratus.'%',11), null, null]; $merges[]='B'.count($rows).':D'.count($rows);
$rows[] = [null];

$rows[] = [C('PECAHAN MENGIKUT CAWANGAN',3),C('',3),C('',3),C('',3)]; $merges[]='A'.count($rows).':D'.count($rows);
$rows[] = [C('Cawangan',1), C('Layak',1), C('Mengundi',1), C('Peratus',1)];
foreach ($layakCaw as $lc) {
    $caw=$lc['cawangan']; $lay=(int)$lc['bil']; $und=$undiCaw[$caw]??0;
    $pct=$lay>0 ? round($und/$lay*100,1).'%' : '0%';
    $rows[] = [C($caw,5), N($lay,6), N($und,6), C($pct,6)];
}

$sheet1 = sheetXml($rows, [30,16,16,12], $merges, $ht);

function sheetKeputusan(array $data, int $bandStyle): string {
    $rows=[]; $merges=[]; $ht=[];
    $rows[]=[C('KEPUTUSAN UNDIAN',2)]; $merges[]='A1:E1'; $ht[1]=24;
    $rows[]=[null];
    foreach ($data as $j) {
        $tajukBand = strtoupper($j['nama']) . ($j['seri'] ? '   ⚠ SERI' : '');
        $rows[]=[C($tajukBand,$bandStyle),C('',$bandStyle),C('',$bandStyle),C('',$bandStyle),C('',$bandStyle)];
        $rn=count($rows); $merges[]='A'.$rn.':E'.$rn; $ht[$rn]=20;
        $rows[]=[C('Bil',1),C('Nama Calon',1),C('Cawangan',1),C('Gred',1),C('Jumlah Undian',1)];
        if (!$j['calon']) {
            $rows[]=[C('',5),C('(Tiada undi diterima)',5),C('',5),C('',5),N(0,6)];
        } else {
            foreach ($j['calon'] as $c) {
                $tText = $c['tie'] ? 7 : 5;
                $tNum  = $c['tie'] ? 8 : 6;
                $rankStyle = ($c['rank']===1 && !$c['tie']) ? 12 : $tNum; 
                $rows[]=[
                    ($c['rank']===1 ? N($c['rank'],12) : N($c['rank'],$tNum)),
                    C($c['nama'],$tText),
                    C($c['cawangan'],$tText),
                    C($c['gred'],$tText),
                    N($c['undi'],$tNum),
                ];
            }
        }
        $rows[]=[null];
    }
    return sheetXml($rows, [6,32,20,10,16], $merges, $ht);
}
$sheet2 = sheetKeputusan($dataA, 3); 
$sheet3 = sheetKeputusan($dataB, 9); 

$sheets = [
    ['name'=>'Statistik',       'xml'=>$sheet1],
    ['name'=>'Kategori A',      'xml'=>$sheet2],
    ['name'=>'Kategori B (AJK)','xml'=>$sheet3],
];

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
.'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
.'<fonts count="5">'
.'<font><sz val="11"/><color rgb="FF16223D"/><name val="Calibri"/></font>'
.'<font><b/><sz val="11"/><color rgb="FF16223D"/><name val="Calibri"/></font>'
.'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
.'<font><b/><sz val="13"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
.'<font><b/><sz val="16"/><color rgb="FF16223D"/><name val="Calibri"/></font>'
.'</fonts>'
.'<fills count="10">'
.'<fill><patternFill patternType="none"/></fill>'
.'<fill><patternFill patternType="gray125"/></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FF4860A8"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FF16223D"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FFD8A818"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FFEEF2F8"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FFFBE3E3"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FFE4ECF7"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FFF7EFD9"/></patternFill></fill>'
.'<fill><patternFill patternType="solid"><fgColor rgb="FFA97D0A"/></patternFill></fill>'
.'</fills>'
.'<borders count="2">'
.'<border><left/><right/><top/><bottom/><diagonal/></border>'
.'<border><left style="thin"><color rgb="FFD9E1EC"/></left><right style="thin"><color rgb="FFD9E1EC"/></right><top style="thin"><color rgb="FFD9E1EC"/></top><bottom style="thin"><color rgb="FFD9E1EC"/></bottom><diagonal/></border>'
.'</borders>'
.'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
.'<cellXfs count="13">'
.'<xf fontId="0" fillId="0" borderId="0"/>' 
.'<xf fontId="2" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 1 header
.'<xf fontId="4" fillId="0" borderId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 2 tajuk
.'<xf fontId="3" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 3 band biru
.'<xf fontId="1" fillId="0" borderId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left"/></xf>' // 4 label tebal
.'<xf fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 5 teks data
.'<xf fontId="1" fillId="0" borderId="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 6 nombor data
.'<xf fontId="0" fillId="6" borderId="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 7 teks seri
.'<xf fontId="1" fillId="6" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 8 nombor seri
.'<xf fontId="3" fillId="9" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 9 band emas
.'<xf fontId="1" fillId="5" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 10 label stat
.'<xf fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 11 nilai stat
.'<xf fontId="2" fillId="4" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 12 rank#1 emas
.'</cellXfs>'
.'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
.'</styleSheet>';

$ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
.'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
.'<Default Extension="xml" ContentType="application/xml"/>'
.'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
.'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
foreach ($sheets as $i=>$s) {
    $ct .= '<Override PartName="/xl/worksheets/sheet'.($i+1).'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
}
$ct .= '</Types>';

$relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
.'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
.'</Relationships>';

$wbSheets=''; $wbRels='';
foreach ($sheets as $i=>$s) {
    $sid=$i+1;
    $wbSheets .= '<sheet name="'.xesc($s['name']).'" sheetId="'.$sid.'" r:id="rId'.$sid.'"/>';
    $wbRels   .= '<Relationship Id="rId'.$sid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$sid.'.xml"/>';
}
$styleRid = count($sheets)+1;
$wbRels .= '<Relationship Id="rId'.$styleRid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
.'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
.'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
.'<sheets>'.$wbSheets.'</sheets></workbook>';

$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
.$wbRels.'</Relationships>';

$tmp = tempnam(sys_get_temp_dir(), 'ksk') . '.xlsx';
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500); exit('Gagal menjana fail Excel.');
}
$zip->addFromString('[Content_Types].xml', $ct);
$zip->addFromString('_rels/.rels', $relsRoot);
$zip->addFromString('xl/workbook.xml', $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
$zip->addFromString('xl/styles.xml', $styles);
foreach ($sheets as $i=>$s) {
    $zip->addFromString('xl/worksheets/sheet'.($i+1).'.xml', $s['xml']);
}
$zip->close();

$namaFail = 'Keputusan_Undian_KSK_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $namaFail . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store');
readfile($tmp);
@unlink($tmp);
exit;