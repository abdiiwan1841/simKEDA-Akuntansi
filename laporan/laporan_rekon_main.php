<?php
function laporan_rekon_main($arg=NULL, $nama=NULL) {
    //$h = '<style>label{font-weight: bold; display: block; width: 150px; float: left;}</style>';
    //drupal_set_html_head($h);
	//drupal_add_css('apbd.css');
	//drupal_add_css('files/css/tablenew.css');
	//drupal_add_js('files/js/kegiatancam.js');
	$qlike='';
	$limit = 10;
    $cetakpdf = '';
	if ($arg) {
		switch($arg) {
			case 'filter':
				$bulan = arg(2);
				$kodeuk = arg(3);
				$tingkat = arg(4);
				$cetakpdf = arg(5);
				break;
				
			case 'excel':
				break;

			default:
				//drupal_access_denied();
				break;
		}
		
	} else {
		$bulan = date('n')-1;;		//variable_get('apbdtahun', 0);
		$tingkat = '3';
		if (isUserSKPD()) {
			$kodeuk = apbd_getuseruk();
		} else {
			$kodeuk = '81';
		}
		
	}
	if ($bulan=='0') $bulan='12';
	
	if (arg(5)=='pdf') {
		$output = gen_report_realisasi_print($bulan, $kodeuk, $tingkat);
		apbd_ExportPDF_P($output, 10, "LAP");
		//return $output;
		
	} else if (arg(5)=='excel') {

		header( "Content-Type: application/vnd.ms-excel" );
		header( "Content-disposition: attachment; filename=Laporan Rekon.xls" );
		header("Pragma: no-cache"); 
		header("Expires: 0");
		$output = gen_report_realisasi_print($bulan, $kodeuk, $tingkat, arg(5));
		echo $output;
		//echo 'John' . "\t" . 'Doe' . "\t" . '555-5555' . "\n";
		 
	} else {
		//drupal_set_message(arg(4));
		$output = gen_report_realisasi($bulan, $kodeuk, $tingkat, false);
		$output_form = drupal_get_form('laporan_rekon_main_form');	
		
		$btn = l('Cetak', 'laporanrekon/filter/' . $bulan . '/'. $kodeuk .'/'.$tingkat. '/pdf' , array ('html' => true, 'attributes'=> array ('class'=>'btn btn-primary glyphicon glyphicon-print')));
		$btn .= '&nbsp;' . l('Excel', 'laporanrekon/filter/' . $bulan . '/'. $kodeuk .'/'.$tingkat. '/excel' , array ('html' => true, 'attributes'=> array ('class'=>'btn btn-primary glyphicon glyphicon-floppy-save')));

		//$btn = '';
		
		return drupal_render($output_form) . $btn . $output . $btn;
		
	}	
	
}

function laporan_rekon_main_form_submit($form, &$form_state) {
	$bulan= $form_state['values']['bulan'];
	$kodeuk = $form_state['values']['kodeuk'];
	$tingkat = $form_state['values']['tingkat'];

	$uri = 'laporanrekon/filter/' . $bulan . '/'. $kodeuk . '/' . $tingkat;
	drupal_goto($uri);
	
}


function laporan_rekon_main_form($form, &$form_state) {
	
	if (isUserSKPD()) {
		$kodeuk = apbd_getuseruk();
	} else {
		$kodeuk = '81';
	}
	$namasingkat = '|SELURUH SKPD';
	$bulan = date('n')-1;
	$tingkat = '3';
	
	if(arg(2)!=null){
		
		$bulan = arg(2);
		$kodeuk = arg(3);
		$tingkat = arg(4);
		
	} 
	if ($bulan=='0') $bulan='12';

	$arr_bulan = array(	
			 '01' => t('JANUARI'), 	
			 '02' => t('FEBRUARI'),
			 '03' => t('MARET'),	
			 '04' => t('APRIL'),	
			 '05' => t('MEI'),	
			 '06' => t('JUNI'),	
			 '07' => t('JULI'),	
			 '08' => t('AGUSTUS'),	
			 '09' => t('SEPTEMBER'),	
			 '10' => t('OKTOBER'),	
			 '11' => t('NOVEMBER'),	
			 '12' => t('DESEMBER'),	
		   );	
	$opttingkat = array();
	$opttingkat['3'] = 'JENIS';
	$opttingkat['4'] = 'OBYEK';
	$opttingkat['5'] = 'RINCIAN';
	
	if ($kodeuk!='ZZ') {
		$query = db_select('unitkerja', 'p');
		$query->fields('p', array('namasingkat','kodeuk'))
			  ->condition('kodeuk',$kodeuk,'=');
		$results = $query->execute();
		if($results){
			foreach($results as $data) {
				$namasingkat= '|' . $data->namasingkat;
			}
		}	
	}
	
	$form['formdata'] = array (
		'#type' => 'fieldset',
		'#title'=> $arr_bulan[$bulan] . $namasingkat . '|' . $opttingkat[$tingkat] . '<em><small class="text-info pull-right">klik disini utk  menampilkan/menyembunyikan pilihan data</small></em>',
		//'#attributes' => array('class' => array('container-inline')),
		'#collapsible' => TRUE, 
		'#collapsed' => TRUE,        
	);	

	
	$form['formdata']['bulan'] = array(
		'#type' => 'select',
		'#title' => 'Bulan',
		'#default_value' => $bulan,	
		'#options' => $arr_bulan,
	);
	
	//SKPD
	if (isUserSKPD()) {
		$form['formdata']['kodeuk'] = array(
			'#type' => 'hidden',
			'#default_value' => $kodeuk,
		);
		
	} else {
		
		$n = 0;
		if (isAdministrator()) {
			$result = db_query('SELECT kodeuk, namasingkat FROM unitkerja ORDER BY namasingkat');	
			
		} else {
		
			global $user;
			$username = $user->name;		
			
			$result = db_query('SELECT unitkerja.kodeuk, unitkerja.namasingkat FROM unitkerja INNER JOIN userskpd ON unitkerja.kodeuk=userskpd.kodeuk WHERE userskpd.username=:username ORDER BY unitkerja.namasingkat', array(':username' => $username));	
		}
		while($row = $result->fetchObject()){
			$option_skpd[$row->kodeuk] = $row->namasingkat; 
			$n++;
		}
		if ($n==0) {
			$result = db_query('SELECT kodeuk, namasingkat FROM unitkerja ORDER BY namasingkat');	
			while($row = $result->fetchObject()){
				$option_skpd[$row->kodeuk] = $row->namasingkat; 
				$n++;
			}
		}
		
		$form['formdata']['kodeuk'] = array(
			'#type' => 'select',
			'#title' =>  t('SKPD'),
			// The entire enclosing div created here gets replaced when dropdown_first
			// is changed.
			'#options' => $option_skpd,
			//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,5
			'#default_value' => $kodeuk,
		);
	}
	
	$form['formdata']['tingkat'] = array(
		'#type' => 'select',
		'#title' =>  t('Tingkat'),
		// The entire enclosing div created here gets replaced when dropdown_first
		// is changed.
		'#options' => $opttingkat,
		//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,
		'#default_value' => $tingkat,
	);
	
	$form['formdata']['submit'] = array(
		'#type' => 'submit',
		'#value' => apbd_button_tampilkan(),
		'#attributes' => array('class' => array('btn btn-success')),
	);
	return $form;
}

function gen_report_realisasi($bulan, $kodeuk, $tingkat) {

$sufixjurnal = 'uk';

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapatan_total_dinas = 0;
$rea_belanja_total_dinas = 0;
$rea_pembiayaan_netto_dinas = 0;

$rea_pendapatan_total_pusat = 0;
$rea_belanja_total_pusat = 0;
$rea_pembiayaan_netto_pusat = 0;

//TABEL
$header = array (
	array('data' => 'Kode','width' => '10px', 'valign'=>'top'),
	array('data' => 'Uraian', 'valign'=>'top'),
	array('data' => 'Anggaran', 'width' => '90px', 'valign'=>'top'),
	array('data' => 'Dinas', 'width' => '90px', 'valign'=>'top'),
	array('data' => 'Pusat', 'width' => '90px', 'valign'=>'top'),
	array('data' => 'Selisih', 'width' => '90px', 'valign'=>'top'),
);
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.anggaran)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasidinas = 0;$realisasipusat = 0;
	$sql = db_select('jurnal' . $sufixjurnal, 'j');
	$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$res = $sql->execute();
	//dpq($sql);
	foreach ($res as $data) {
		$realisasidinas = $data->realisasi;
	}
	$sql = db_select('jurnal', 'j');
	$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasipusat = $data->realisasi;
	}
	$rows[] = array(
		array('data' => $datas->kodea, 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasipusat- $realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
	$agg_pendapata_total = $datas->anggaran;
	$rea_pendapatan_total_dinas = $realisasidinas;
	$rea_pendapatan_total_pusat = $realisasipusat;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperuk', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$sql->condition('k.kodea', $datas->kodea, '='); 
	$query->addExpression('SUM(ag.anggaran)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasidinas = 0;$realisasipusat = 0;
		$sql = db_select('jurnal' . $sufixjurnal, 'j');
		$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasidinas = $data->realisasi;
		}
		$sql = db_select('jurnal', 'j');
		$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasipusat = $data->realisasi;
		}
		
		$rows[] = array(
			array('data' => '<strong>' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasipusat - $realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperuk', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.anggaran)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasidinas = 0;$realisasipusat = 0;
			$sql = db_select('jurnal' . $sufixjurnal, 'j');
			$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasidinas = $data->realisasi;
			}
			$sql = db_select('jurnal', 'j');
			$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasipusat = $data->realisasi;
			}
		
			$rows[] = array(
				array('data' => $data_jen->kodej, 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasidinas), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasipusat), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasipusat- $realisasidinas), 'align' => 'right', 'valign'=>'top'),
			);
			
			if ($tingkat>'3') {
				//OBYEK
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperuk', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.anggaran)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasidinas = 0;$realisasipusat = 0;
					$sql = db_select('jurnal' . $sufixjurnal, 'j');
					$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasidinas = $data->realisasi;
					}
					$sql = db_select('jurnal', 'j');
					$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasipusat = $data->realisasi;
					}
				
					$rows[] = array(
						array('data' => $data_oby->kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasidinas) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasipusat) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasipusat- $realisasidinas), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperuk', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.anggaran)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
						
						$realisasidinas = 0;$realisasipusat = 0;
						$sql = db_select('jurnal' . $sufixjurnal, 'j');
						$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
						$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$sql->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $sql->execute();
						foreach ($res as $data) {
							$realisasidinas = $data->realisasi;
						}
						$sql = db_select('jurnal', 'j');
						$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
						$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$sql->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $sql->execute();
						foreach ($res as $data) {
							$realisasipusat = $data->realisasi;
						}					
						$rows[] = array(
							array('data' => $data_rek->kodero, 'align' => 'left', 'valign'=>'top'),
							array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($realisasidinas) . '</em>', 'align' => 'right', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($realisasipusat) . '</em>', 'align' => 'right', 'valign'=>'top'),  
							array('data' => '<em>'. apbd_fn($realisasipusat- $realisasidinas) . '</em>', 'align' => 'right', 'valign'=>'top'),
						);
					
					}	//obyek					
					
					}
				}	//obyek			
			
			}	//if tingkat obyek
		}	//jenis
		
		
	}
	

}	//foreach ($results as $datas)

// * BELANJA * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperkeg', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.anggaran)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasidinas = 0;$realisasipusat = 0;
	$sql = db_select('jurnal' . $sufixjurnal, 'j');
	$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasidinas = $data->realisasi;
	}
	$sql = db_select('jurnal', 'j');
	$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasipusat = $data->realisasi;
	}
	$rows[] = array(
		array('data' => $datas->kodea, 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasipusat- $realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total_dinas = $realisasidinas;
	$rea_belanja_total_pusat = $realisasipusat;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.anggaran)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasidinas = 0;$realisasipusat = 0;
		$sql = db_select('jurnal' . $sufixjurnal, 'j');
		$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
		$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasidinas = $data->realisasi;
		}
		$sql = db_select('jurnal', 'j');
		$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
		$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasipusat = $data->realisasi;
		}
			
		
		$rows[] = array(
			array('data' => '<strong>' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasipusat- $realisasidinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.anggaran)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasidinas = 0;$realisasipusat = 0;
			$sql = db_select('jurnal' . $sufixjurnal, 'j');
			$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
			$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasidinas = $data->realisasi;
			}
		
			$sql = db_select('jurnal', 'j');
			$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
			$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasipusat = $data->realisasi;
			}

			$rows[] = array(
				array('data' => $data_jen->kodej, 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasidinas), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasipusat), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasipusat- $realisasidinas), 'align' => 'right', 'valign'=>'top'),
			);
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.anggaran)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasidinas = 0;$realisasipusat = 0;
					$sql = db_select('jurnal' . $sufixjurnal, 'j');
					$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
					$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasidinas = $data->realisasi;
					}
				
					$sql = db_select('jurnal', 'j');
					$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
					$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasipusat = (is_null($data->realisasi)?0: $data->realisasi);
					}

					$rows[] = array(
						array('data' => $data_oby->kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasidinas) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasipusat) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasipusat- $realisasidinas), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.anggaran)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasidinas = 0;$realisasipusat = 0;
							$sql = db_select('jurnal' . $sufixjurnal, 'j');
							$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
							$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$sql->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $sql->execute();
							foreach ($res as $data) {
								$realisasidinas = $data->realisasi;
							}
						
							$sql = db_select('jurnal', 'j');
							$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
							$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
							$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$sql->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $sql->execute();
							foreach ($res as $data) {
								$realisasipusat = $data->realisasi;
							}

							$rows[] = array(
								array('data' => $data_rek->kodero, 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($realisasidinas) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($realisasipusat) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($realisasipusat- $realisasidinas) . '</em>', 'align' => 'right', 'valign'=>'top'),
							);
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	

}	//foreach ($results as $datas)
//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto_dinas = $rea_pendapatan_total_dinas - $rea_belanja_total_dinas;
$realisasi_netto_pusat = $rea_pendapatan_total_pusat - $rea_belanja_total_pusat;
$rows[] = array(
	array('data' => '', 'align' => 'left', 'valign'=>'top'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'align' => 'left', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto_dinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto_pusat) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto_pusat - $realisasi_netto_dinas) . '</strong>', 'align' => 'right', 'valign'=>'top'),
);


//RENDER	
$tabel_data = theme('table', array('header' => $header, 'rows' => $rows ));

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}


function gen_report_realisasi_print($bulan, $kodeuk, $tingkat, $cetakpdf) {

$sufixjurnal = 'uk';

if ($kodeuk == 'ZZ')
	$skpd = 'KABUPATEN JEPARA';
else {
	$results = db_query('select namauk from {unitkerja} where kodeuk=:kodeuk', array(':kodeuk' => $kodeuk));
	foreach ($results as $datas) {
		$skpd = $datas->namauk;
	};
}

$rows[] = array(
	array('data' => 'LAPORAN REALISASI ANGGARAN', 'colspan'=>6, 'width' => '510px', 'align'=>'center','style'=>'font-size:80%;border:none'),
);
$rows[] = array(
	array('data' => $skpd, 'width' => '510px', 'colspan'=>6, 'align'=>'center','style'=>'font-size:80%;border:none'),
);

if ($bulan=='0') {
	$rows[] = array(
		array('data' => 'TAHUN : ' . apbd_tahun(), 'width' => '510px', 'colspan'=>6, 'align'=>'center','style'=>'font-size:80%;border:none'),
	);
	
} else {
	$rows[] = array(
		array('data' => 'BULAN : ' . $bulan . ' TAHUN : ' . apbd_tahun(), 'colspan'=>6, 'width' => '510px', 'align'=>'center','style'=>'font-size:80%;border:none'),
	);
}

$tabel_data = theme('table', array('header' => null, 'rows' => $rows ));

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapata_total_dinas = 0;
$rea_belanja_total_dinas = 0;
$rea_pembiayaan_netto_dinas = 0;

$rea_pendapata_total_pusat = 0;
$rea_belanja_total_pusat = 0;
$rea_pembiayaan_netto_pusat = 0;

$rows = null;
//TABEL

if($cetakpdf == 'excel'){
//$header = array();
$header[] = array (
	array('data' => 'KODE','width' => '45px',  'align'=>'center','style'=>'font-size:80%;border-left:1px solid black;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'URAIAN','width' => '185px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'ANGGARAN', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'REALISASI Dinas', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => 'REALISASI Pusat', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => 'SELISIH', 'width' => '70px','align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
);
}else{
$header[] = array (
	array('data' => 'KODE','width' => '45px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-left:1px solid black;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'URAIAN','width' => '185px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'REALISASI', 'width' => '140px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'SELISIH', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
);
$header[] = array (
	array('data' => 'Dinas', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => 'Pusat', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
);
}
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.anggaran)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');

//dpq ($query);
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasidinas = 0; $realisasipusat = 0;
	$sql = db_select('jurnal' . $sufixjurnal, 'j');
	$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasidinas = $data->realisasi;
	}
	$sql = db_select('jurnal', 'j');
	$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasipusat = $data->realisasi;
	}
	$rows[] = array(
		array('data' => $datas->kodea, 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasipusat - $realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);
	
	$agg_pendapata_total = $datas->anggaran;
	$rea_pendapata_total_dinas = $realisasidinas;
	$rea_pendapata_total_pusat = $realisasipusat;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperuk', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$sql->condition('k.kodea', $datas->kodea, '='); 
	$query->addExpression('SUM(ag.anggaran)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasidinas = 0; $realisasipusat = 0;
		$sql = db_select('jurnal' . $sufixjurnal, 'j');
		$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasidinas = $data->realisasi;
		}
		$sql = db_select('jurnal', 'j');
		$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasipusat = $data->realisasi;
		}
		
		$rows[] = array(
			array('data' => '<strong>' . $data_kel->kodek . '</strong>', 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasipusat - $realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);		
		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperuk', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.anggaran)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasidinas = 0; $realisasipusat = 0;
			$sql = db_select('jurnal' . $sufixjurnal, 'j');
			$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasidinas = $data->realisasi;
			}

			$sql = db_select('jurnal', 'j');
			$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasipusat = $data->realisasi;
			}			
			$rows[] = array(
				array('data' => $data_jen->kodej, 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasidinas), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasipusat), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasipusat - $realisasidinas), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);
			
			if ($tingkat>'3') {
				//OBYEK
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperuk', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.anggaran)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasidinas = 0; $realisasipusat = 0;
					$sql = db_select('jurnal' . $sufixjurnal, 'j');
					$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasidinas = $data->realisasi;
					}

					$sql = db_select('jurnal', 'j');
					$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasipusat = $data->realisasi;
					}
					
					$rows[] = array(
						array('data' => $data_jen->kodej . '.' . substr($data_oby->kodeo,-2), 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasidinas) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasipusat) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasipusat - $realisasidinas), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperuk', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.anggaran)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
						
						$realisasidinas = 0; $realisasipusat = 0;
						$sql = db_select('jurnal' . $sufixjurnal, 'j');
						$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
						$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$sql->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $sql->execute();
						foreach ($res as $data) {
							$realisasidinas = $data->realisasi;
						}

						$sql = db_select('jurnal', 'j');
						$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
						$sql->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$sql->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $sql->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $sql->execute();
						foreach ($res as $data) {
							$realisasipusat = $data->realisasi;
						}						
						$rows[] = array(
							array('data' => $data_jen->kodej . '.' . substr($data_oby->kodeo,-2) . '.'. substr($data_rek->kodero, -3), 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
							array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($realisasidinas) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($realisasipusat) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($realisasipusat - $realisasidinas) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						);
					
					}	//obyek					
					
					}
				}	//obyek			
			
			}	//if tingkat obyek
		}	//jenis
		
		
	}
	
	$rows[] = array(
		array('data' => '', 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);

}	//foreach ($results as $datas)

// * BELANJA * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperkeg', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.anggaran)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasidinas = 0; $realisasipusat = 0;
	$sql = db_select('jurnal' . $sufixjurnal, 'j');
	$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasidinas = $data->realisasi;
	}

	$sql = db_select('jurnal', 'j');
	$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
	$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
	$sql->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $sql->execute();
	foreach ($res as $data) {
		$realisasipusat = $data->realisasi;
	}

	$rows[] = array(
		array('data' => $datas->kodea, 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasipusat - $realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total_dinas = $realisasidinas;
	$rea_belanja_total_pusat = $realisasidinas;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.anggaran)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasidinas = 0; $realisasipusat = 0;
		$sql = db_select('jurnal' . $sufixjurnal, 'j');
		$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
		$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasidinas = $data->realisasi;
		}
			
		$sql = db_select('jurnal', 'j');
		$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
		$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
		$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$sql->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $sql->execute();
		foreach ($res as $data) {
			$realisasipusat = $data->realisasi;
		}			
		
		$rows[] = array(
			array('data' => '<strong>' . $data_kel->kodek . '</strong>', 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasipusat) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasipusat - $realisasidinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.anggaran)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasidinas = 0; $realisasipusat = 0;
			$sql = db_select('jurnal' . $sufixjurnal, 'j');
			$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
			$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasidinas = $data->realisasi;
			}
		
			$sql = db_select('jurnal', 'j');
			$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
			$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
			$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$sql->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $sql->execute();
			foreach ($res as $data) {
				$realisasipusat = $data->realisasi;
			}

			$rows[] = array(
				array('data' => $data_jen->kodej, 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasidinas), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasipusat), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasipusat - $realisasidinas), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.anggaran)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasidinas = 0; $realisasipusat = 0;
					$sql = db_select('jurnal' . $sufixjurnal, 'j');
					$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
					$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasidinas = $data->realisasi;
					}
				
					$sql = db_select('jurnal', 'j');
					$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
					$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
					$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$sql->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $sql->execute();
					foreach ($res as $data) {
						$realisasipusat = $data->realisasi;
					}

					$rows[] = array(
						array('data' => $data_jen->kodej . '.' . substr($data_oby->kodeo,-2), 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasidinas) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' =>apbd_fn($realisasipusat) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasipusat - $realisasidinas), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.anggaran)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasidinas = 0; $realisasipusat = 0;
							$sql = db_select('jurnal' . $sufixjurnal, 'j');
							$sql->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
							$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$sql->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $sql->execute();
							foreach ($res as $data) {
								$realisasidinas = $data->realisasi;
							}

							$sql = db_select('jurnal', 'j');
							$sql->innerJoin('jurnalitem', 'ji', 'j.jurnalid=ji.jurnalid');
							$sql->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
							$sql->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$sql->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $sql->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $sql->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $sql->execute();
							foreach ($res as $data) {
								$realisasipusat = $data->realisasi;
							}
							
							$rows[] = array(
								array('data' => $data_jen->kodej . '.' . substr($data_oby->kodeo,-2) . '.'. substr($data_rek->kodero, -3), 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($realisasidinas) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($realisasipusat) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($realisasipusat - $realisasidinas) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	

}	//foreach ($results as $datas)
//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto_dinas = $rea_pendapata_total_dinas - $rea_belanja_total_dinas;
$realisasi_netto_pusat = $rea_pendapata_total_pusat - $rea_belanja_total_pusat;

$rows[] = array(
	array('data' => '', 'width' => '45px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'width' => '185px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto_dinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto_pusat) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto_pusat - $realisasi_netto_dinas) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
);


$rows[] = array(
		array('data' => '', 'width' => '510px', 'align'=>'right','style'=>'font-size:80%;border-top:1px solid black;'),
		
	);

//RENDER	
//$tabel_data .= theme('table', array('header' => $header, 'rows' => $rows ));
$tabel_data .= createT($header, $rows);

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}


?>


