<?php
function laporan_kegpage_main($arg=NULL, $nama=NULL) {
    //$h = '<style>label{font-weight: bold; display: block; width: 125px; float: left;}</style>';
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
				$margin =arg(5);
				$tanggal =arg(6);
				$hal1 = arg(7);
				$cetakpdf = arg(8);
				break;
				
			case 'excel':
				break;

			default:
				//drupal_access_denied();
				break;
		}
		
	} else {
		$bulan = date('n');		//variable_get('apbdtahun', 0);
		$tingkat = '3';
		$margin = '10'; 
		$hal1 = '1'; 
		$tanggal = date('j F Y');
		if (isUserSKPD()) {
			$kodeuk = apbd_getuseruk();
		} else {
			$kodeuk = '81';
		}
		
		
	}
	
	drupal_set_title('BELANJA');
	
	 
	if ($cetakpdf=='pdf') {
		$output = gen_report_realisasi_print_resume($bulan, $kodeuk, $tingkat,$margin,$tanggal);
		$_SESSION["hal1"] = $hal1;
		apbd_ExportPDF_P($output, $margin, "LAP");
		
		//return $output;
				
	}else if ($cetakpdf=='pdf2') {
		$output = gen_report_realisasi_printbag($bulan, $kodeuk, $tingkat,$margin,$tanggal,arg(9));
		$_SESSION["hal1"] = $hal1;
		apbd_ExportPDF_P($output, $margin, "LAP");
		
		//return $output;
				
	} else {
		//$output = gen_report_realisasi_kegiatan_resume($bulan, $kodeuk, $tingkat);
		$output = '';
		$output_form = drupal_get_form('laporan_kegpage_main_form');	
		
		$ada = false;
		drupal_set_message($kodeuk);
		$res = db_query('select page from {kegiatanpage} where kodeuk=:kodeuk', array(':kodeuk'=>$kodeuk));
		foreach ($res as $data) {
			$ada = true;
			$btnx .= '<li><a href="/laporankegpage/filter/' . $bulan . '/'. $kodeuk . '/'. $tingkat . '/'. $margin . '/'. urlencode($tanggal)  . '/' . $hal1 . '/pdf/' . $data->page . '">Bagian-' . $data->page . '</a></li>';
		};		
		
		if ($ada==false) {
			$btn = l('Cetak', 'laporankegpage/filter/' . $bulan . '/'. $kodeuk . '/'. $tingkat . '/'. $margin . '/'. urlencode($tanggal)  . '/' . $hal1 . '/pdf' , array ('html' => true, 'attributes'=> array ('class'=>'btn btn-primary')));
		} else {
			$btn = '<div class="btn-group">' .
					'<button type="button" class="btn btn-primary glyphicon glyphicon-print dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
					' Cetak <span class="caret"></span>' .
					'</button>' .
						'<ul class="dropdown-menu">' .
						
						$btnx .
						
						'</ul>' .
					'</div>';
		}
		
				
		return drupal_render($output_form) . $btn . $output . $btn;
		
	}	
	
}

function laporan_kegpage_main_form_submit($form, &$form_state) {
	$kodeuk = $form_state['values']['kodeuk'];
	
	if($form_state['clicked_button']['#value'] == $form_state['values']['submitpage']) {
		kegiatan_paging();
	} else {

		$bulan= $form_state['values']['bulan'];
		
		$tingkat = $form_state['values']['tingkat'];
		$margin = $form_state['values']['margin'];
		$tanggal = $form_state['values']['tanggal'];
		$hal1 = $form_state['values']['hal1'];
		
		$uri = 'laporankegpage/filter/' . $bulan . '/'. $kodeuk . '/' . $tingkat. '/' . $margin. '/' . $tanggal . '/' . $hal1;
		drupal_goto($uri);
	}
}


function laporan_kegpage_main_form($form, &$form_state) {
	
	if (isUserSKPD()) {
		$kodeuk = apbd_getuseruk();
	} else {
		$kodeuk = '81';
	}
	$namasingkat = '|BPKAD';
	$bulan = date('n');
	$tingkat = '3';
	$margin = '10';
	$tanggal = date('j F Y');
	$hal1 = '1';
	
	if(arg(2)!=null){
		
		$bulan = arg(2);
		$kodeuk = arg(3);
		$tingkat = arg(4);
		$margin =arg(5);
		$tanggal =arg(6);
		$hal1 =arg(7);
	} 
	
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
		'#title'=> $bulan . $namasingkat . '<em><small class="text-info pull-right">klik disini utk  menampilkan/menyembunyikan pilihan data</small></em>',
		//'#attributes' => array('class' => array('container-inline')),
		'#collapsible' => TRUE, 
		'#collapsed' => TRUE,        
	);	

	//SKPD
	if (isUserSKPD()) {
		$form['formdata']['kodeuk'] = array(
			'#type' => 'hidden',
			'#default_value' => $kodeuk,
		);
		
	} else {
		$query = db_select('unitkerja', 'p');
		$query->fields('p', array('namasingkat','kodeuk'));
		$query->orderBy('kodedinas', 'ASC');
		$results = $query->execute();
		$optskpd = array();
		if($results){
			foreach($results as $data) {
			  $optskpd[$data->kodeuk] = $data->namasingkat; 
			}
		}
		
		$form['formdata']['kodeuk'] = array(
			'#type' => 'select',
			'#title' =>  t('SKPD'),
			// The entire enclosing div created here gets replaced when dropdown_first
			// is changed.
			'#options' => $optskpd,
			//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,5
			'#default_value' => $kodeuk,
		);
	}
	
	$form['formdata']['bulan'] = array(
		'#type' => 'select',
		'#title' => 'Bulan',
		'#default_value' => $bulan,	
		'#options' => array(	
			 '0' => t('SETAHUN'), 	
			 '1' => t('JANUARI'), 	
			 '2' => t('FEBRUARI'),
			 '3' => t('MARET'),	
			 '4' => t('APRIL'),	
			 '5' => t('MEI'),	
			 '6' => t('JUNI'),	 
			 '7' => t('JULI'),	
			 '8' => t('AGUSTUS'),	
			 '9' => t('SEPTEMBER'),	
			 '10' => t('OKTOBER'),	
			 '11' => t('NOVEMBER'),	
			 '12' => t('DESEMBER'),	
		   ),
	);
	$opttingkat = array();
	$opttingkat['3'] = 'Jenis';
	$opttingkat['4'] = 'Obyek';
	$opttingkat['5'] = 'Rincian';
	$form['formdata']['tingkat'] = array(
		'#type' => 'select',
		'#title' =>  t('Tingkat'),
		// The entire enclosing div created here gets replaced when dropdown_first
		// is changed.
		'#options' => $opttingkat,
		//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,
		'#default_value' => $tingkat,
	);
	
	$form['formdata']['margin']= array(
		'#type' => 'textfield',
		'#title' => 'Margin',
		'#default_value' => $margin,
	);
	$form['formdata']['hal1']= array(
		'#type' => 'textfield',
		'#title' => 'Halaman #1',
		'#default_value' => $hal1,
	);	
	$form['formdata']['tanggal']= array(
		'#type' => 'textfield',
		'#title' => 'Tanggal',
		'#default_value' => $tanggal ,
	);

	$form['formdata']['submitpage'] = array(
		'#type' => 'submit',
		'#value' => 'Paging',
		'#attributes' => array('class' => array('btn btn-info')),
	);
	$form['formdata']['submit'] = array(
		'#type' => 'submit',
		'#value' => apbd_button_tampilkan(),
		'#attributes' => array('class' => array('btn btn-success')),
	);
	return $form;
}

function gen_report_realisasi_kegiatan($bulan, $kodeuk, $tingkat) {

if (isUserSKPD()) {
	$sufixjurnal = 'uk';
} else {
	$sufixjurnal = '';
}


$results = db_query('select kodedinas from {unitkerja} where kodeuk=:kodeuk', array(':kodeuk' => $kodeuk));
foreach ($results as $datas) {
	$kodedinas = $datas->kodedinas;
};

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapata_total = 0;
$rea_belanja_total = 0;
$rea_pembiayaan_netto = 0;
 
//TABEL
$header = array (
	array('data' => 'Kode','width' => '15px', 'valign'=>'top'),
	array('data' => 'Uraian', 'valign'=>'top'),
	array('data' => 'Anggaran', 'width' => '80px', 'valign'=>'top'),
	array('data' => 'Realiasi', 'width' => '80px', 'valign'=>'top'),
	array('data' => '%', 'width' => '15px', 'valign'=>'top'),
);
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rows[] = array(
		array('data' =>  '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
	$agg_pendapata_total = $datas->anggaran;
	$rea_pendapata_total = $realisasi;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperuk', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperuk', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			$rows[] = array(
				array('data' => $kodej, 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
			);
			
			if ($tingkat>'3') {
				//OBYEK
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperuk', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
					$rows[] = array(
						array('data' => $kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperuk', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
						
						$realisasi = 0;
						$query = db_select('jurnal' . $sufixjurnal, 'j');
						$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
						$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$query->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $query->execute();
						foreach ($res as $data) {
							$realisasi = $data->realisasi;
						}
					
						$rows[] = array(
							array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'align' => 'left', 'valign'=>'top'),
							array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
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
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
$query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->condition('keg.inaktif', '0', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rows[] = array(
		array('data' =>  '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total = $realisasi;
	
	//KELOMPOK BELANJA TIDAK LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '1', '='); 
	$query->condition('keg.inaktif', '0', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
		$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
			
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$kodej = $data_kel->kodek . '.000.000.' . substr($data_jen->kodej,-1);
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
			$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}

			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;			
			$rows[] = array(
				array('data' => $kodej , 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
			);
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
					$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}

					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);					
					$rows[] = array(
						array('data' => $kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
							$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$query->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}
						
							$rows[] = array(
								array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
							);
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	

	//KELOMPOK BELANJA LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '2', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
		$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
			
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		//PROGRAM
		$query = db_select('program', 'p');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodepro=p.kodepro');
		$query->fields('p', array('kodepro', 'program'));
		$query->addExpression('SUM(keg.anggaran)', 'anggaran');
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('keg.jenis', '2', '='); 
		$query->groupBy('p.kodepro');
		$query->orderBy('p.program');
		$results_pro = $query->execute(); 
		foreach ($results_pro as $data_pro) {

			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
			$query->addExpression('SUM(j.total)', 'realisasi');
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodepro = $kodedinas . '.' . $data_pro->kodepro;
			$rows[] = array(
				array('data' => '<strong>' . $kodepro . '</strong>', 'align' => 'left', 'valign'=>'top'),
				array('data' => '<strong>' . strtoupper($data_pro->program) . '</strong>', 'align' => 'left', 'valign'=>'top'),
				array('data' => '<strong>' . apbd_fn($data_pro->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
				array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
				array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_pro->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			);				
			
			
			//KEGIATAN
			$query = db_select('kegiatanskpd', 'keg');
			$query->fields('keg', array('kodekeg', 'kegiatan', 'total'));
			$query->condition('keg.kodeuk', $kodeuk, '='); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			$query->condition('keg.jenis', '2', '='); 
			$query->orderBy('keg.kodepro', 'ASC');
			$query->orderBy('keg.kodekeg', 'ASC');
			$results_keg = $query->execute(); 
			foreach ($results_keg as $data_keg) {
				
				$realisasi = 0;
				
				$query = db_select('jurnal' . $sufixjurnal, 'j');
				$query->addExpression('SUM(j.total)', 'realisasi');
				$query->condition('j.kodekeg', $data_keg->kodekeg, '='); 
				
				if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
				$res = $query->execute();
				foreach ($res as $data) {
					$realisasi = $data->realisasi;
				} 
				

				//$kodekeg = $kodepro . '.' . substr($data_keg->kodekeg,-3);
				$kodekeg = $kodedinas . '.' . $data_pro->kodepro . '.' . substr($data_keg->kodekeg,-3);
				$rows[] = array(
					array('data' => '<strong>' . $kodekeg  . '</strong>', 'align' => 'left', 'valign'=>'top'),
					array('data' => '<strong>' . strtoupper($data_keg->kegiatan) . '</strong>', 'align' => 'left', 'valign'=>'top'),
					array('data' => '<strong>' . apbd_fn($data_keg->total) . '</strong>', 'align' => 'right', 'valign'=>'top'),
					array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
					array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_keg->total, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
				);				
				
				
				//JENIS
				
				$query = db_select('jenis', 'j');
				$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('j', array('kodej', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('j.kodek', $data_kel->kodek, '='); 
				$query->groupBy('j.kodej');
				$query->orderBy('j.kodej');
				$results_jen = $query->execute();	
				foreach ($results_jen as $data_jen) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
					$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
					$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					
					//dpq($query);
					
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
				
					$kodej = $kodekeg  . '.' . $data_jen->kodej;
					$rows[] = array(
						array('data' => $kodej , 'align' => 'left', 'valign'=>'top'),
						array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					
					//OBYEK
					if ($tingkat>'3') {
						$query = db_select('obyek', 'o');
						$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('o', array('kodeo', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
						$query->condition('o.kodej', $data_jen->kodej, '='); 
						$query->groupBy('o.kodeo');
						$query->orderBy('o.kodeo');
						$results_oby = $query->execute();	
						foreach ($results_oby as $data_oby) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
							$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
							$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}
							
							$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
							$rows[] = array(
								array('data' => $kodeo, 'align' => 'left', 'valign'=>'top'),
								array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
								array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
								array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
								array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
							);
							
							//REKENING
							if ($tingkat=='5') {
								$query = db_select('rincianobyek', 'ro');
								$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
								$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
								$query->fields('ro', array('kodero', 'uraian'));
								$query->addExpression('SUM(ag.jumlah)', 'anggaran');
								$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
								$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
								$query->groupBy('ro.kodero');
								$query->orderBy('ro.kodero');
								$results_rek = $query->execute();	
								foreach ($results_rek as $data_rek) {
									
									$realisasi = 0;
									$query = db_select('jurnal' . $sufixjurnal, 'j');
									$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
									$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
									$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
									$query->condition('ji.kodero', $data_rek->kodero, '='); 
									$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
									if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
									if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
									$res = $query->execute();
									foreach ($res as $data) {
										$realisasi = $data->realisasi;
									}
								
									$rows[] = array(
										array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'align' => 'left', 'valign'=>'top'),
										array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
										array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
										array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
									);
								
								}	//obyek					
								
							}	//rekening
						}	//obyek			
						
					}	//if obyek
				}	//jenis
				
				
			
			}
					
			
				
		}	
		
	}
		
}	//foreach ($results as $datas)
//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto = $rea_pendapata_total - $rea_belanja_total;
$rows[] = array(
	array('data' => '', 'align' => 'left', 'valign'=>'top'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'align' => 'left', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
);


if ($kodeuk=='ZZ') {
	//PEMBIAYAAN
	$anggaran_netto_p = 0;
	$realisasi_netto_p = 0;

	$rows[] = array(
		array('data' => '6', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>PEMBIAYAAN</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '', 'align' => 'right', 'valign'=>'top'),
		array('data' => '', 'align' => 'right', 'valign'=>'top'),
		array('data' => '', 'align' => 'right', 'valign'=>'top'),
	);
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperda', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
		
		$rows[] = array(
			array('data' => '<strong>' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
	
		
		if ($data_kel->kodek=='61') {
			$anggaran_netto_p += $data_kel->anggaran;
			$realisasi_netto_p += $realisasi;

		} else	{	
			$anggaran_netto_p -= $data_kel->anggaran;
			$realisasi_netto_p -= $realisasi;
		}
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperda', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
		
			$rows[] = array(
				array('data' => $data_jen->kodej, 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
			);
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperda', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
				
					$rows[] = array(
						array('data' => $data_oby->kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperda', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
							$query->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}
						
							$rows[] = array(
								array('data' => $data_rek->kodero, 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
							);
						
						}	//obyek					
						
					}
				}	//obyek			
				
			}	//tingkat obyek
		}	//jenis
		
		
	}
	
	//SURPLUS DEFIIT
	$rows[] = array(
		array('data' => '', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>PEMBIAYAAN NETTO</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($anggaran_netto_p) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi_netto_p) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto_p, $realisasi_netto_p)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);	

	//SILPA
	
	$anggaran_netto += $anggaran_netto_p;
	$realisasi_netto += $realisasi_netto_p;
	$rows[] = array(
		array('data' => '', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>SISA LEBIH ANGGARAN TAHUN BERJALAN</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
}


//RENDER	
$tabel_data = theme('table', array('header' => $header, 'rows' => $rows ));

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}


function gen_report_realisasi_print($bulan, $kodeuk, $tingkat,$margin,$tanggal) {


$results = db_query('select namauk,pimpinannama,pimpinanjabatan,pimpinannip,kodedinas from {unitkerja} where kodeuk=:kodeuk', array(':kodeuk' => $kodeuk));
foreach ($results as $datas) {
	$kodedinas = $datas->kodedinas;
	$skpd = $datas->namauk;
	$pimpinannama = $datas->pimpinannama;
	$pimpinanjabatan = $datas->pimpinanjabatan;
	$pimpinannip = $datas->pimpinannip;
};

$rows[] = array(
	array('data' => 'LAPORAN REALISASI ANGGARAN', 'width' => '510px', 'align'=>'center','style'=>'border:none'),
);
$rows[] = array(
	array('data' => $skpd, 'width' => '510px', 'align'=>'center','style'=>'border:none'),
);

if ($bulan=='0') {
	$rows[] = array(
		array('data' => 'TAHUN : ' . apbd_tahun(), 'width' => '510px', 'align'=>'center','style'=>'border:none'),
	);
	
} else {
	$rows[] = array(
		array('data' => 'BULAN : ' . $bulan . ' TAHUN : ' . apbd_tahun(), 'width' => '510px', 'align'=>'center','style'=>'border:none'),
	);
}

$tabel_data = theme('table', array('header' => null, 'rows' => $rows ));

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapata_total = 0;
$rea_belanja_total = 0;
$rea_pembiayaan_netto = 0;

//TABEL 
$rows = null;
//$header = array();
$header[] = array (
	array('data' => 'KODE','width' => '100px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-left:1px solid black;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'URAIAN','width' => '170px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'REALISASI', 'width' => '100px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'SISA ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
);
$header[] = array (
	array('data' => 'Rupiah', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => 'Prsn', 'width' => '30px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
);
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

if (isUserSKPD()) {
	$sufixjurnal = 'uk';
} else {
	$sufixjurnal = '';
}
foreach ($results as $datas) {
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rows[] = array(
		array('data' => '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);	
	
	$agg_pendapata_total = $datas->anggaran;
	$rea_pendapata_total = $realisasi;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperuk', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
				
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);			
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperuk', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			$rows[] = array(
				array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);
			
			if ($tingkat>'3') {
				//OBYEK
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperuk', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
					$rows[] = array(
						array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperuk', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
						
						$realisasi = 0;
						$query = db_select('jurnal' . $sufixjurnal, 'j');
						$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
						$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$query->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $query->execute();
						foreach ($res as $data) {
							$realisasi = $data->realisasi;
						}
					

						$rows[] = array(
							array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
							array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						);						
					
					}	//obyek					
					
					}
				}	//obyek			
			
			}	//if tingkat obyek
		}	//jenis
		
		
	}
	
	//Batas
	$rows[] = array(
		array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);						

}	//foreach ($results as $datas)

// * BELANJA * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperkeg', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
$query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();
$rea_belanja_total=0;
foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rea_belanja_total = $realisasi;
	$rows[] = array(
		array('data' => '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total = $realisasi;
	
	//KELOMPOK BELANJA TIDAK LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '1', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
		$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}

		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);		
				
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
			$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			$rows[] = array(
				array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
					$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);	
					$rows[] = array(
						array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
							$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$query->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}				

							$rows[] = array(
								array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);							
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	

	//KELOMPOK BELANJA LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '2', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
		$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
			
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);			
		
		//PROGRAM
		$query = db_select('program', 'p');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodepro=p.kodepro');
		$query->fields('p', array('kodepro', 'program'));
		$query->addExpression('SUM(keg.anggaran)', 'anggaran');
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('keg.jenis', '2', '='); 
		$query->groupBy('p.kodepro');
		$query->orderBy('p.program');
		$results_pro = $query->execute(); 
		foreach ($results_pro as $data_pro) {

			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
			$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodepro = $kodedinas . '.' . $data_pro->kodepro;		
			$rows[] = array(
				array('data' => $kodepro, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => strtoupper($data_pro->program), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_pro->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_pro->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_pro->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);			
			
			
			//KEGIATAN
			$query = db_select('kegiatanskpd', 'keg');
			$query->fields('keg', array('kodekeg', 'kegiatan', 'total'));
			$query->condition('keg.kodeuk', $kodeuk, '='); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			$query->condition('keg.jenis', '2', '='); 
			$query->orderBy('keg.kodepro', 'ASC');
			$query->orderBy('keg.kodekeg', 'ASC');
			$results_keg = $query->execute(); 
			foreach ($results_keg as $data_keg) {
				
				$realisasi = 0;
				
				$query = db_select('jurnal' . $sufixjurnal, 'j');
				$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
				$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
				$query->condition('j.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
				if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
				$res = $query->execute();
				foreach ($res as $data) {
					$realisasi = $data->realisasi;
				} 
				

				$kodekeg = $kodedinas . '.' . $data_pro->kodepro . '.' . substr($data_keg->kodekeg,-3);
				$rows[] = array(
					array('data' => $kodekeg, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
					array('data' => strtoupper($data_keg->kegiatan), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($data_keg->total), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn1(apbd_hitungpersen($data_keg->total, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($data_keg->total- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				);					
				
				
				//JENIS
				
				$query = db_select('jenis', 'j');
				$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('j', array('kodej', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('j.kodek', $data_kel->kodek, '='); 
				$query->groupBy('j.kodej');
				$query->orderBy('j.kodej'); 
				$results_jen = $query->execute();	
				foreach ($results_jen as $data_jen) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
					$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
					$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					
					//dpq($query);
					
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
				
					$kodej = $kodekeg  . '.' . $data_jen->kodej;
					$rows[] = array(
						array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					
					//OBYEK
					if ($tingkat>'3') {
						$query = db_select('obyek', 'o');
						$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('o', array('kodeo', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
						$query->condition('o.kodej', $data_jen->kodej, '='); 
						$query->groupBy('o.kodeo');
						$query->orderBy('o.kodeo');
						$results_oby = $query->execute();	
						foreach ($results_oby as $data_oby) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
							$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
							$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}
							
							$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
							$rows[] = array(
								array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);		
							
							//REKENING
							if ($tingkat=='5') {
								$query = db_select('rincianobyek', 'ro');
								$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
								$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
								$query->fields('ro', array('kodero', 'uraian'));
								$query->addExpression('SUM(ag.jumlah)', 'anggaran');
								$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
								$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
								$query->orderBy('ro.kodero');
								$results_rek = $query->execute();	
								foreach ($results_rek as $data_rek) {
									 
									$realisasi = 0;
									$query = db_select('jurnal' . $sufixjurnal, 'j');
									$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
									$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
									$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
									$query->condition('ji.kodero', $data_rek->kodero, '='); 
									$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
									$query->condition('j.kodekeg', $data_keg->kodekeg, '='); 
									$query->condition('keg.kodeuk', $kodeuk, '='); 
									if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
									$res = $query->execute();
									foreach ($res as $data) {
										$realisasi = $data->realisasi;
									}
								

									$rows[] = array(
										array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
										array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
									);									
								
								}	//obyek					
								
							}	//rekening
						}	//obyek			
						
					}	//if obyek
				}	//jenis
				
				
			
			}
					
			
				
		}	
		
	}
		
}	//foreach ($results as $datas)


//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto = $rea_pendapata_total - $rea_belanja_total;
$rows[] = array(
	array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto- $realisasi_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
);


//Batas
$rows[] = array(
	array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
);						
if(!isSuperuser()){
		if(arg(6)!='pdf'){
			$rows[] = array(
				array('data' => '', 'width' => '510px', 'align'=>'right','style'=>'font-size:80%;border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;'),
				
			);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => 'Jepara, '.$tanggal ,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;font-size:80%;'),
									
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => $pimpinanjabatan,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;font-size:80%;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => $pimpinannama,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;text-decoration:underline;font-size:80%;'),					
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;border-bottom:1px solid black;'),
						array('data' => 'NIP. ' . $pimpinannip,'width' => '255px', 'align'=>'center','style'=>'border-bottom:1px solid black;border-right:1px solid black;font-size:80%;'),					
					);	
		}
	}
$tabel_data .= createT($header, $rows);

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}

function gen_report_realisasi_printbag($bulan, $kodeuk, $tingkat,$margin,$tanggal,$bag) {


$results = db_query('select namauk,pimpinannama,pimpinanjabatan,pimpinannip,kodedinas from {unitkerja} where kodeuk=:kodeuk', array(':kodeuk' => $kodeuk));
foreach ($results as $datas) {
	$kodedinas = $datas->kodedinas;
	$skpd = $datas->namauk;
	$pimpinannama = $datas->pimpinannama;
	$pimpinanjabatan = $datas->pimpinanjabatan;
	$pimpinannip = $datas->pimpinannip;
};



$tabel_data = theme('table', array('header' => null, 'rows' => $rows ));

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapata_total = 0;
$rea_belanja_total = 0;
$rea_pembiayaan_netto = 0;

//TABEL 
$rows = null;
//$header = array();
$header[] = array (
	array('data' => 'KODE','width' => '100px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-left:1px solid black;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'URAIAN','width' => '170px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'REALISASI', 'width' => '100px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'SISA ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
);
$header[] = array (
	array('data' => 'Rupiah', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => 'Prsn', 'width' => '30px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
);
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

if (isUserSKPD()) {
	$sufixjurnal = 'uk';
} else {
	$sufixjurnal = '';
}
$agg_pendapata_total=0;
foreach ($results as $datas) {
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}	
							
$agg_pendapata_total = $datas->anggaran;
$rea_pendapata_total = $realisasi;
}	//foreach ($results as $datas)

// * BELANJA * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperkeg', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
$query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ji.kodekeg');
	$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rea_belanja_total = $realisasi;
	
	/*$rows[] = array(
		array('data' => '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);*/
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total = $realisasi;
	
	//KELOMPOK BELANJA TIDAK LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '1', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
		$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}

		/*$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);*/		
				
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
			$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			/*$rows[] = array(
				array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);*/
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
					$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);	
					$rows[] = array(
						array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
							$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$query->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}				

							$rows[] = array(
								array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);							
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	
	
	

	//KELOMPOK BELANJA LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '2', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
		$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
			
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);			
		$batasbawah=0+(($bag-1)*3);
		$range=3;
		//PROGRAM
		$query = db_select('program', 'p');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodepro=p.kodepro');
		$query->fields('p', array('kodepro', 'program'));
		$query->addExpression('SUM(keg.anggaran)', 'anggaran');
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('keg.jenis', '2', '='); 
		$query->groupBy('p.kodepro');
		$query->orderBy('p.program');
		$query->range($batasbawah, $range);
		$results_pro = $query->execute(); 
		foreach ($results_pro as $data_pro) {

			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
			$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodepro = $kodedinas . '.' . $data_pro->kodepro;		
			$rows[] = array(
				array('data' => $kodepro, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => strtoupper($data_pro->program), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_pro->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_pro->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_pro->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);			
			
			
			//KEGIATAN
			$query = db_select('kegiatanskpd', 'keg');
			$query->fields('keg', array('kodekeg', 'kegiatan', 'total'));
			$query->condition('keg.kodeuk', $kodeuk, '='); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			$query->condition('keg.jenis', '2', '='); 
			$query->orderBy('keg.kodepro', 'ASC');
			$query->orderBy('keg.kodekeg', 'ASC');
			$results_keg = $query->execute(); 
			foreach ($results_keg as $data_keg) {
				
				$realisasi = 0;
				
				$query = db_select('jurnal' . $sufixjurnal, 'j');
				$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
				$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
				$query->condition('j.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
				if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
				$res = $query->execute();
				foreach ($res as $data) {
					$realisasi = $data->realisasi;
				} 
				

				$kodekeg = $kodedinas . '.' . $data_pro->kodepro . '.' . substr($data_keg->kodekeg,-3);
				$rows[] = array(
					array('data' => $kodekeg, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
					array('data' => strtoupper($data_keg->kegiatan), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($data_keg->total), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn1(apbd_hitungpersen($data_keg->total, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($data_keg->total- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				);					
				
				
				//JENIS
				
				$query = db_select('jenis', 'j');
				$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('j', array('kodej', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('j.kodek', $data_kel->kodek, '='); 
				$query->groupBy('j.kodej');
				$query->orderBy('j.kodej'); 
				$results_jen = $query->execute();	
				foreach ($results_jen as $data_jen) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
					$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
					$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					
					//dpq($query);
					
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
				
					$kodej = $kodekeg  . '.' . $data_jen->kodej;
					$rows[] = array(
						array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					
					//OBYEK
					if ($tingkat>'3') {
						$query = db_select('obyek', 'o');
						$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('o', array('kodeo', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
						$query->condition('o.kodej', $data_jen->kodej, '='); 
						$query->groupBy('o.kodeo');
						$query->orderBy('o.kodeo');
						$results_oby = $query->execute();	
						foreach ($results_oby as $data_oby) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
							$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
							$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
							$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}
							
							$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
							$rows[] = array(
								array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);		
							
							//REKENING
							if ($tingkat=='5') {
								$query = db_select('rincianobyek', 'ro');
								$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
								$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
								$query->fields('ro', array('kodero', 'uraian'));
								$query->addExpression('SUM(ag.jumlah)', 'anggaran');
								$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
								$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
								$query->orderBy('ro.kodero');
								$results_rek = $query->execute();	
								foreach ($results_rek as $data_rek) {
									 
									$realisasi = 0;
									$query = db_select('jurnal' . $sufixjurnal, 'j');
									$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
									$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=j.kodekeg');
									$query->addExpression('SUM(ji.debet-ji.kredit)', 'realisasi');
									$query->condition('ji.kodero', $data_rek->kodero, '='); 
									$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
									$query->condition('j.kodekeg', $data_keg->kodekeg, '='); 
									$query->condition('keg.kodeuk', $kodeuk, '='); 
									if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
									$res = $query->execute();
									foreach ($res as $data) {
										$realisasi = $data->realisasi;
									}
								

									$rows[] = array(
										array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
										array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
									);									
								
								}	//obyek					
								
							}	//rekening
						}	//obyek			
						
					}	//if obyek
				}	//jenis
				
				
			
			}
					
			
				
		}	
		
	}
		
}	//foreach ($results as $datas)


//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto = $rea_pendapata_total - $rea_belanja_total;
$rows[] = array(
	array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto- $realisasi_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
);


//Batas
$rows[] = array(
	array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
);						
if(!isSuperuser()){
		if(arg(6)!='pdf'){
			$rows[] = array(
				array('data' => '', 'width' => '510px', 'align'=>'right','style'=>'font-size:80%;border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;'),
				
			);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => 'Jepara, '.$tanggal ,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;font-size:80%;'),
									
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => $pimpinanjabatan,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;font-size:80%;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => $pimpinannama,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;text-decoration:underline;font-size:80%;'),					
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;border-bottom:1px solid black;'),
						array('data' => 'NIP. ' . $pimpinannip,'width' => '255px', 'align'=>'center','style'=>'border-bottom:1px solid black;border-right:1px solid black;font-size:80%;'),					
					);	
		}
	}
$tabel_data .= createT($header, $rows);

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}


function gen_report_realisasi_kegiatan_resume($bulan, $kodeuk, $tingkat) {

if (isUserSKPD()) {
	$sufixjurnal = 'uk';
} else {
	$sufixjurnal = '';
}


$results = db_query('select kodedinas from {unitkerja} where kodeuk=:kodeuk', array(':kodeuk' => $kodeuk));
foreach ($results as $datas) {
	$kodedinas = $datas->kodedinas;
};

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapata_total = 0;
$rea_belanja_total = 0;
$rea_pembiayaan_netto = 0;
 
//TABEL
$header = array (
	array('data' => 'Kode','width' => '15px', 'valign'=>'top'),
	array('data' => 'Uraian', 'valign'=>'top'),
	array('data' => 'Anggaran', 'width' => '80px', 'valign'=>'top'),
	array('data' => 'Realiasi', 'width' => '80px', 'valign'=>'top'),
	array('data' => '%', 'width' => '15px', 'valign'=>'top'),
);
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rows[] = array(
		array('data' =>  '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
	$agg_pendapata_total = $datas->anggaran;
	$rea_pendapata_total = $realisasi;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperuk', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperuk', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			$rows[] = array(
				array('data' => $kodej, 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
			);
			
			if ($tingkat>'3') {
				//OBYEK
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperuk', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
					$rows[] = array(
						array('data' => $kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperuk', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
						
						$realisasi = 0;
						$query = db_select('jurnal' . $sufixjurnal, 'j');
						$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
						$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$query->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $query->execute();
						foreach ($res as $data) {
							$realisasi = $data->realisasi;
						}
					
						$rows[] = array(
							array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'align' => 'left', 'valign'=>'top'),
							array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
							array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
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
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
$query->addExpression('SUM(ag.realisasi)', 'realisasi');
$query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->condition('keg.inaktif', '0', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$rows[] = array(
		array('data' =>  '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($datas->realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $datas->realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total = $datas->realisasi;
	
	//KELOMPOK BELANJA TIDAK LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->addExpression('SUM(ag.realisasi)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '1', '='); 
	$query->condition('keg.inaktif', '0', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $data_kel->realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		$query->addExpression('SUM(ag.realisasi)', 'realisasi');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;			
			$rows[] = array(
				array('data' => $kodej , 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->realisasi), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $data_jen->realisasi)), 'align' => 'right', 'valign'=>'top'),
			);
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->addExpression('SUM(ag.realisasi)', 'realisasi');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);					
					$rows[] = array(
						array('data' => $kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->realisasi) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $data_oby->realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->addExpression('SUM(ag.realisasi)', 'realisasi');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$rows[] = array(
								array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($data_rek->realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $data_rek->realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
							);
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	

	//KELOMPOK BELANJA LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->addExpression('SUM(ag.realisasi)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '2', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = $datas->realisasi;
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
		
		//PROGRAM
		$query = db_select('program', 'p');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodepro=p.kodepro');
		$query->innerJoin('anggperkeg', 'ag', 'keg.kodekeg=ag.kodekeg');
		$query->fields('p', array('kodepro', 'program'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		$query->addExpression('SUM(ag.realisasi)', 'realisasi');
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('keg.jenis', '2', '='); 
		$query->groupBy('p.kodepro');
		$query->orderBy('p.program');
		$results_pro = $query->execute(); 
		foreach ($results_pro as $data_pro) {

			$realisasi = $data_pro->realisasi;

			$kodepro = $kodedinas . '.' . $data_pro->kodepro;
			$rows[] = array(
				array('data' => '<strong>' . $kodepro . '</strong>', 'align' => 'left', 'valign'=>'top'),
				array('data' => '<strong>' . strtoupper($data_pro->program) . '</strong>', 'align' => 'left', 'valign'=>'top'),
				array('data' => '<strong>' . apbd_fn($data_pro->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
				array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
				array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_pro->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			);				
			
			
			//KEGIATAN
			$query = db_select('kegiatanskpd', 'keg');
			$query->innerJoin('anggperkeg', 'ag', 'keg.kodekeg=ag.kodekeg');
			$query->fields('keg', array('kodekeg', 'kegiatan'));
			$query->addExpression('SUM(ag.jumlah)', 'anggaran');
			$query->addExpression('SUM(ag.realisasi)', 'realisasi');
			
			$query->condition('keg.kodeuk', $kodeuk, '='); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			$query->condition('keg.jenis', '2', '='); 
			$query->groupBy('keg.kodekeg');
			$query->orderBy('keg.kodepro', 'ASC');
			$query->orderBy('keg.kodekeg', 'ASC');
			$results_keg = $query->execute(); 
			foreach ($results_keg as $data_keg) {
				
				$realisasi = $data_keg->realisasi;
				
				//$kodekeg = $kodepro . '.' . substr($data_keg->kodekeg,-3);
				$kodekeg = $kodedinas . '.' . $data_pro->kodepro . '.' . substr($data_keg->kodekeg,-3);
				$rows[] = array(
					array('data' => '<strong>' . $kodekeg  . '</strong>', 'align' => 'left', 'valign'=>'top'),
					array('data' => '<strong>' . strtoupper($data_keg->kegiatan) . '</strong>', 'align' => 'left', 'valign'=>'top'),
					array('data' => '<strong>' . apbd_fn($data_keg->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
					array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
					array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_keg->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
				);				
				
				
				//JENIS
				
				$query = db_select('jenis', 'j');
				$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('j', array('kodej', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->addExpression('SUM(ag.realisasi)', 'realisasi');
				$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('j.kodek', $data_kel->kodek, '='); 
				$query->groupBy('j.kodej');
				$query->orderBy('j.kodej');
				$results_jen = $query->execute();	
				foreach ($results_jen as $data_jen) {
					
					$realisasi = $data_jen->realisasi;

				
					$kodej = $kodekeg  . '.' . $data_jen->kodej;
					$rows[] = array(
						array('data' => $kodej , 'align' => 'left', 'valign'=>'top'),
						array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					
					//OBYEK
					if ($tingkat>'3') {
						$query = db_select('obyek', 'o');
						$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('o', array('kodeo', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->addExpression('SUM(ag.realisasi)', 'realisasi');
						$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
						$query->condition('o.kodej', $data_jen->kodej, '='); 
						$query->groupBy('o.kodeo');
						$query->orderBy('o.kodeo');
						$results_oby = $query->execute();	
						foreach ($results_oby as $data_oby) {
							
							$realisasi = $data_oby->realisasi;

							
							$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
							$rows[] = array(
								array('data' => $kodeo, 'align' => 'left', 'valign'=>'top'),
								array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
								array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
								array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
								array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
							);
							
							//REKENING
							if ($tingkat=='5') {
								$query = db_select('rincianobyek', 'ro');
								$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
								$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
								$query->fields('ro', array('kodero', 'uraian'));
								$query->addExpression('SUM(ag.jumlah)', 'anggaran');
								$query->addExpression('SUM(ag.realisasi)', 'realisasi');
								$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
								$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
								$query->groupBy('ro.kodero');
								$query->orderBy('ro.kodero');
								$results_rek = $query->execute();	
								foreach ($results_rek as $data_rek) {
									
									$realisasi = $data_rek->realisasi;
								
									$rows[] = array(
										array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'align' => 'left', 'valign'=>'top'),
										array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
										array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
										array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
									);
								
								}	//obyek					
								
							}	//rekening
						}	//obyek			
						
					}	//if obyek
				}	//jenis
				
				
			
			}
					
			
				
		}	
		
	}
		
}	//foreach ($results as $datas)
//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto = $rea_pendapata_total - $rea_belanja_total;
$rows[] = array(
	array('data' => '', 'align' => 'left', 'valign'=>'top'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'align' => 'left', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
);


if ($kodeuk=='ZZ') {
	//PEMBIAYAAN
	$anggaran_netto_p = 0;
	$realisasi_netto_p = 0;

	$rows[] = array(
		array('data' => '6', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>PEMBIAYAAN</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '', 'align' => 'right', 'valign'=>'top'),
		array('data' => '', 'align' => 'right', 'valign'=>'top'),
		array('data' => '', 'align' => 'right', 'valign'=>'top'),
	);
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperda', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
		
		$rows[] = array(
			array('data' => '<strong>' . $data_kel->kodek . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'align' => 'left', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'align' => 'right', 'valign'=>'top'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		);		
	
		
		if ($data_kel->kodek=='61') {
			$anggaran_netto_p += $data_kel->anggaran;
			$realisasi_netto_p += $realisasi;

		} else	{	
			$anggaran_netto_p -= $data_kel->anggaran;
			$realisasi_netto_p -= $realisasi;
		}
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperda', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
		
			$rows[] = array(
				array('data' => $data_jen->kodej, 'align' => 'left', 'valign'=>'top'),
				array('data' => $data_jen->uraian, 'align' => 'left', 'valign'=>'top'),
				array('data' => apbd_fn($data_jen->anggaran), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn($realisasi), 'align' => 'right', 'valign'=>'top'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
			);
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperda', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
				
					$rows[] = array(
						array('data' => $data_oby->kodeo, 'align' => 'left', 'valign'=>'top'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data_oby->anggaran) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn($realisasi) , 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)), 'align' => 'right', 'valign'=>'top'),
					);
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperda', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasi = 0;
							$query = db_select('jurnal' . $sufixjurnal, 'j');
							$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
							$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
							$query->condition('ji.kodero', $data_rek->kodero, '='); 
							if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
							$res = $query->execute();
							foreach ($res as $data) {
								$realisasi = $data->realisasi;
							}
						
							$rows[] = array(
								array('data' => $data_rek->kodero, 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'align' => 'left', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'align' => 'right', 'valign'=>'top'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'align' => 'right', 'valign'=>'top'),
							);
						
						}	//obyek					
						
					}
				}	//obyek			
				
			}	//tingkat obyek
		}	//jenis
		
		
	}
	
	//SURPLUS DEFIIT
	$rows[] = array(
		array('data' => '', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>PEMBIAYAAN NETTO</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($anggaran_netto_p) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi_netto_p) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto_p, $realisasi_netto_p)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);	

	//SILPA
	
	$anggaran_netto += $anggaran_netto_p;
	$realisasi_netto += $realisasi_netto_p;
	$rows[] = array(
		array('data' => '', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>SISA LEBIH ANGGARAN TAHUN BERJALAN</strong>', 'align' => 'left', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'align' => 'right', 'valign'=>'top'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'align' => 'right', 'valign'=>'top'),
	);
	
}


//RENDER	
$tabel_data = theme('table', array('header' => $header, 'rows' => $rows ));

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}


function gen_report_realisasi_print_resume($bulan, $kodeuk, $tingkat,$margin,$tanggal) {


$results = db_query('select namauk,pimpinannama,pimpinanjabatan,pimpinannip,kodedinas from {unitkerja} where kodeuk=:kodeuk', array(':kodeuk' => $kodeuk));
foreach ($results as $datas) {
	$kodedinas = $datas->kodedinas;
	$skpd = $datas->namauk;
	$pimpinannama = $datas->pimpinannama;
	$pimpinanjabatan = $datas->pimpinanjabatan;
	$pimpinannip = $datas->pimpinannip;
};

$rows[] = array(
	array('data' => 'LAPORAN REALISASI ANGGARAN', 'width' => '510px', 'align'=>'center','style'=>'border:none'),
);
$rows[] = array(
	array('data' => $skpd, 'width' => '510px', 'align'=>'center','style'=>'border:none'),
);

if ($bulan=='0') {
	$rows[] = array(
		array('data' => 'TAHUN : ' . apbd_tahun(), 'width' => '510px', 'align'=>'center','style'=>'border:none'),
	);
	
} else {
	$rows[] = array(
		array('data' => 'BULAN : ' . $bulan . ' TAHUN : ' . apbd_tahun(), 'width' => '510px', 'align'=>'center','style'=>'border:none'),
	);
}

$tabel_data = theme('table', array('header' => null, 'rows' => $rows ));

$agg_pendapata_total = 0;
$agg_belanja_total = 0;
$agg_pembiayaan_netto = 0;

$rea_pendapata_total = 0;
$rea_belanja_total = 0;
$rea_pembiayaan_netto = 0;

//TABEL 
$rows = null;
//$header = array();
$header[] = array (
	array('data' => 'KODE','width' => '100px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-left:1px solid black;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'URAIAN','width' => '170px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'REALISASI', 'width' => '100px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
	array('data' => 'SISA ANGGARAN', 'width' => '70px', 'rowspan'=>2, 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;font-weight: bold'),
);
$header[] = array (
	array('data' => 'Rupiah', 'width' => '70px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => 'Prsn', 'width' => '30px', 'align'=>'center','style'=>'font-size:80%;border-top:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
);
$rows = array();

// * PENDAPATAN * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperuk', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '4', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();

if (isUserSKPD()) {
	$sufixjurnal = 'uk';
} else {
	$sufixjurnal = '';
}
foreach ($results as $datas) {
	
	$realisasi = 0;
	$query = db_select('jurnal' . $sufixjurnal, 'j');
	$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
	$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
	if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
	$query->condition('ji.kodero', db_like($datas->kodea) . '%', 'LIKE'); 
	if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	$res = $query->execute();
	foreach ($res as $data) {
		$realisasi = $data->realisasi;
	}
	$rows[] = array(
		array('data' => '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);	
	
	$agg_pendapata_total = $datas->anggaran;
	$rea_pendapata_total = $realisasi;
	
	//KELOMPOK
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperuk', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->fields('k', array('kodek', 'uraian'));
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {
		$realisasi = 0;
		$query = db_select('jurnal' . $sufixjurnal, 'j');
		$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
		$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
		$query->condition('ji.kodero', db_like($data_kel->kodek) . '%', 'LIKE'); 
		if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
		if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
		$res = $query->execute();
		foreach ($res as $data) {
			$realisasi = $data->realisasi;
		}
				
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);			
		
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperuk', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			
			$realisasi = 0;
			$query = db_select('jurnal' . $sufixjurnal, 'j');
			$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
			$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
			$query->condition('ji.kodero', db_like($data_jen->kodej) . '%', 'LIKE'); 
			if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
			if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
			$res = $query->execute();
			foreach ($res as $data) {
				$realisasi = $data->realisasi;
			}
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			$rows[] = array(
				array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);
			
			if ($tingkat>'3') {
				//OBYEK
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperuk', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					
					$realisasi = 0;
					$query = db_select('jurnal' . $sufixjurnal, 'j');
					$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
					$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
					$query->condition('ji.kodero', db_like($data_oby->kodeo) . '%', 'LIKE'); 
					if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
					if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
					$res = $query->execute();
					foreach ($res as $data) {
						$realisasi = $data->realisasi;
					}
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
					$rows[] = array(
						array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperuk', 'ag', 'ro.kodero=ag.kodero');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						if ($kodeuk!='ZZ') $query->condition('ag.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
						
						$realisasi = 0;
						$query = db_select('jurnal' . $sufixjurnal, 'j');
						$query->innerJoin('jurnalitem' . $sufixjurnal, 'ji', 'j.jurnalid=ji.jurnalid');
						$query->addExpression('SUM(ji.kredit-ji.debet)', 'realisasi');
						$query->condition('ji.kodero', $data_rek->kodero, '='); 
						if ($kodeuk!='ZZ') $query->condition('j.kodeuk', $kodeuk, '='); 
						if ($bulan>0) $query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
						$res = $query->execute();
						foreach ($res as $data) {
							$realisasi = $data->realisasi;
						}
					

						$rows[] = array(
							array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
							array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						);						
					
					}	//obyek					
					
					}
				}	//obyek			
			
			}	//if tingkat obyek
		}	//jenis
		
		
	}
	
	//Batas
	$rows[] = array(
		array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);						

}	//foreach ($results as $datas)

// * BELANJA * //
//AKUN
$query = db_select('anggaran', 'a');
$query->innerJoin('anggperkeg', 'ag', 'a.kodea=left(ag.kodero,1)');
$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
$query->fields('a', array('kodea', 'uraian'));
$query->addExpression('SUM(ag.jumlah)', 'anggaran');
$query->addExpression('SUM(ag.realisasi)', 'realisasi');
$query->condition('keg.kodeuk', $kodeuk, '='); 
$query->condition('a.kodea', '5', '='); 
$query->groupBy('a.kodea');
$query->orderBy('a.kodea');
$results = $query->execute();
$rea_belanja_total=0;
foreach ($results as $datas) {
	//$query->where('EXTRACT(MONTH FROM j.tanggal) <= :month', array('month' => $bulan));
	
	$realisasi = $datas->realisasi;

	$rea_belanja_total = $realisasi;
	$rows[] = array(
		array('data' => '<strong>' . $kodedinas . '.' . $datas->kodea . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
		array('data' => '<strong>' . $datas->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($datas->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		array('data' => '<strong>' . apbd_fn($datas->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
	);
	
	$agg_belanja_total = $datas->anggaran;
	$rea_belanja_total = $realisasi;
	
	//KELOMPOK BELANJA TIDAK LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->addExpression('SUM(ag.realisasi)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '1', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {

		$realisasi = $data_kel->realisasi;

		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.000.000.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);		
				
		//JENIS
		$query = db_select('jenis', 'j');
		$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
		$query->fields('j', array('kodej', 'uraian'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		$query->addExpression('SUM(ag.realisasi)', 'realisasi');
		if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('j.kodek', $data_kel->kodek, '='); 
		$query->groupBy('j.kodej');
		$query->orderBy('j.kodej');
		$results_jen = $query->execute();	
		foreach ($results_jen as $data_jen) {
			

			$realisasi = $data_jen->realisasi;
			
			$kodej = $kodedinas . '.000.000.' . $data_jen->kodej;
			$rows[] = array(
				array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);
			
			
			//OBYEK
			if ($tingkat>'3') {
				$query = db_select('obyek', 'o');
				$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('o', array('kodeo', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->addExpression('SUM(ag.realisasi)', 'realisasi');
				if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
				$query->condition('o.kodej', $data_jen->kodej, '='); 
				$query->groupBy('o.kodeo');
				$query->orderBy('o.kodeo');
				$results_oby = $query->execute();	
				foreach ($results_oby as $data_oby) {
					

					$realisasi = $data_oby->realisasi;
					
					$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);	
					$rows[] = array(
						array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					//REKENING
					if ($tingkat=='5') {
						$query = db_select('rincianobyek', 'ro');
						$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('ro', array('kodero', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->addExpression('SUM(ag.realisasi)', 'realisasi');
						if ($kodeuk!='ZZ') $query->condition('keg.kodeuk', $kodeuk, '='); 
						$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
						$query->groupBy('ro.kodero');
						$query->orderBy('ro.kodero');
						$results_rek = $query->execute();	
						foreach ($results_rek as $data_rek) {
							
							$realisasi = $data_rek->realisasi;

							$rows[] = array(
								array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);							
						
						}	//obyek					
						
					}	//rekening
				}	//obyek			
				
			}	//if obyek
		}	//jenis
		
		
	}
	

	//KELOMPOK BELANJA LANGSUNG
	$query = db_select('kelompok', 'k');
	$query->innerJoin('anggperkeg', 'ag', 'k.kodek=left(ag.kodero,2)');
	$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
	$query->fields('k', array('kodek', 'uraian'));
	$query->addExpression('SUM(ag.jumlah)', 'anggaran');
	$query->addExpression('SUM(ag.realisasi)', 'realisasi');
	$query->condition('keg.kodeuk', $kodeuk, '='); 
	$query->condition('k.kodea', $datas->kodea, '='); 
	$query->condition('keg.jenis', '2', '='); 
	$query->groupBy('k.kodek');
	$query->orderBy('k.kodek');
	$results_kel = $query->execute();	
	
	foreach ($results_kel as $data_kel) {

		$realisasi = $data_kel->realisasi;
			
		
		$rows[] = array(
			array('data' => '<strong>' . $kodedinas . '.' . $data_kel->kodek . '</strong>', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
			array('data' => '<strong>' . $data_kel->uraian . '</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($data_kel->anggaran, $realisasi)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			array('data' => '<strong>' . apbd_fn($data_kel->anggaran- $realisasi) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
		);			
		
		//PROGRAM
		$query = db_select('program', 'p');
		$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodepro=p.kodepro');
		$query->innerJoin('anggperkeg', 'ag', 'keg.kodekeg=ag.kodekeg');
		$query->fields('p', array('kodepro', 'program'));
		$query->addExpression('SUM(ag.jumlah)', 'anggaran');
		$query->addExpression('SUM(ag.realisasi)', 'realisasi');
		$query->condition('keg.kodeuk', $kodeuk, '='); 
		$query->condition('keg.jenis', '2', '='); 
		$query->groupBy('p.kodepro');
		$query->orderBy('p.program');
		$results_pro = $query->execute(); 
		foreach ($results_pro as $data_pro) {

			$realisasi = $data_pro->realisasi;
			
			$kodepro = $kodedinas . '.' . $data_pro->kodepro;		
			$rows[] = array(
				array('data' => $kodepro, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
				array('data' => strtoupper($data_pro->program), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_pro->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn1(apbd_hitungpersen($data_pro->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				array('data' => apbd_fn($data_pro->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
			);			
			
			
			//KEGIATAN
			$query = db_select('kegiatanskpd', 'keg');
			$query->innerJoin('anggperkeg', 'ag', 'keg.kodekeg=ag.kodekeg');
			$query->fields('keg', array('kodekeg', 'kegiatan'));
			$query->addExpression('SUM(ag.jumlah)', 'anggaran');
			$query->addExpression('SUM(ag.realisasi)', 'realisasi');
			
			$query->condition('keg.kodeuk', $kodeuk, '='); 
			$query->condition('keg.kodepro', $data_pro->kodepro, '='); 
			$query->condition('keg.jenis', '2', '='); 
			$query->groupBy('keg.kodekeg');
			$query->orderBy('keg.kodepro', 'ASC');
			$query->orderBy('keg.kodekeg', 'ASC');
			$results_keg = $query->execute(); 
			foreach ($results_keg as $data_keg) {
				

				$realisasi = $data_keg->realisasi;
				 
				

				$kodekeg = $kodedinas . '.' . $data_pro->kodepro . '.' . substr($data_keg->kodekeg,-3);
				$rows[] = array(
					array('data' => $kodekeg, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
					array('data' => strtoupper($data_keg->kegiatan), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($data_keg->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn1(apbd_hitungpersen($data_keg->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					array('data' => apbd_fn($data_keg->anggaran - $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
				);					
				
				
				//JENIS
				
				$query = db_select('jenis', 'j');
				$query->innerJoin('anggperkeg', 'ag', 'j.kodej=left(ag.kodero,3)');
				$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
				$query->fields('j', array('kodej', 'uraian'));
				$query->addExpression('SUM(ag.jumlah)', 'anggaran');
				$query->addExpression('SUM(ag.realisasi)', 'realisasi');
				$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
				$query->condition('j.kodek', $data_kel->kodek, '='); 
				$query->groupBy('j.kodej');
				$query->orderBy('j.kodej');
				$results_jen = $query->execute();	
				foreach ($results_jen as $data_jen) {
					

					$realisasi = $data_jen->realisasi;
					
				
					$kodej = $kodekeg  . '.' . $data_jen->kodej;
					$rows[] = array(
						array('data' => $kodej, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
						array('data' => $data_jen->uraian, 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_jen->anggaran), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn1(apbd_hitungpersen($data_jen->anggaran, $realisasi)), 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
						array('data' => apbd_fn($data_jen->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
					);					
					
					
					//OBYEK
					if ($tingkat>'3') {
						$query = db_select('obyek', 'o');
						$query->innerJoin('anggperkeg', 'ag', 'o.kodeo=left(ag.kodero,5)');
						$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
						$query->fields('o', array('kodeo', 'uraian'));
						$query->addExpression('SUM(ag.jumlah)', 'anggaran');
						$query->addExpression('SUM(ag.realisasi)', 'realisasi');
						$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
						$query->condition('o.kodej', $data_jen->kodej, '='); 
						$query->groupBy('o.kodeo');
						$query->orderBy('o.kodeo');
						$results_oby = $query->execute();	
						foreach ($results_oby as $data_oby) {
							

							$realisasi = $data_oby->realisasi;
							
							
							$kodeo = $kodej . '.' . substr($data_oby->kodeo,-2);
							$rows[] = array(
								array('data' => $kodeo, 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
								array('data' => ucfirst(strtolower($data_oby->uraian)), 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($data_oby->anggaran) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($realisasi) , 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn1(apbd_hitungpersen($data_oby->anggaran, $realisasi)) , 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
								array('data' => apbd_fn($data_oby->anggaran- $realisasi), 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
							);		
							
							//REKENING
							if ($tingkat=='5') {
								$query = db_select('rincianobyek', 'ro');
								$query->innerJoin('anggperkeg', 'ag', 'ro.kodero=ag.kodero');
								$query->innerJoin('kegiatanskpd', 'keg', 'keg.kodekeg=ag.kodekeg');
								$query->fields('ro', array('kodero', 'uraian'));
								$query->addExpression('SUM(ag.jumlah)', 'anggaran');
								$query->addExpression('SUM(ag.realisasi)', 'realisasi');
								$query->condition('keg.kodekeg', $data_keg->kodekeg, '='); 
								$query->condition('ro.kodeo', $data_oby->kodeo, '='); 
								$query->orderBy('ro.kodero');
								$results_rek = $query->execute();	
								foreach ($results_rek as $data_rek) {
									 

									$realisasi = $data_rek->realisasi;
									
								

									$rows[] = array(
										array('data' => $kodeo . '.' . substr($data_rek->kodero,-3), 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;'),
										array('data' => '<em>'. ucfirst(strtolower($data_rek->uraian)) . '</em>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn1(apbd_hitungpersen($data_rek->anggaran, $realisasi)) . '</em>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
										array('data' => '<em>'. apbd_fn($data_rek->anggaran- $realisasi) . '</em>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;'),
									);									
								
								}	//obyek					
								
							}	//rekening
						}	//obyek			
						
					}	//if obyek
				}	//jenis
				
				
			
			}
					
			
				
		}	
		
	}
		
}	//foreach ($results as $datas)


//SURPLUS DEFIIT
$anggaran_netto = $agg_pendapata_total - $agg_belanja_total;
$realisasi_netto = $rea_pendapata_total - $rea_belanja_total;
$rows[] = array(
	array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>SURPLUS / (DEFISIT)</strong>', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($realisasi_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn1(apbd_hitungpersen($anggaran_netto, $realisasi_netto)) . '</strong>', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
	array('data' => '<strong>' . apbd_fn($anggaran_netto- $realisasi_netto) . '</strong>', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-right:1px solid black;border-top:1px solid black;'),
);


//Batas
$rows[] = array(
	array('data' => '', 'width' => '100px', 'align'=>'left','style'=>'font-size:80%;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '170px', 'align'=>'left','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '30px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
	array('data' => '', 'width' => '70px', 'align'=>'right','style'=>'font-size:80%;border-bottom:1px solid black;border-right:1px solid black;'),
);						
if(!isSuperuser()){
		if(arg(6)!='pdf'){
			$rows[] = array(
				array('data' => '', 'width' => '510px', 'align'=>'right','style'=>'font-size:80%;border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;'),
				
			);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => 'Jepara, '.$tanggal ,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;font-size:80%;'),
									
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => $pimpinanjabatan,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;font-size:80%;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;'),
							
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;'),
						array('data' => $pimpinannama,'width' => '255px', 'align'=>'center','style'=>'border-right:1px solid black;text-decoration:underline;font-size:80%;'),					
					);
			$rows[] = array(
						array('data' => '','width' => '255px', 'align'=>'center','style'=>'border-left:1px solid black;border-bottom:1px solid black;'),
						array('data' => 'NIP. ' . $pimpinannip,'width' => '255px', 'align'=>'center','style'=>'border-bottom:1px solid black;border-right:1px solid black;font-size:80%;'),					
					);	
		}
	}
$tabel_data .= createT($header, $rows);

//return drupal_render($apbdrupiah) . $chart_persen . $tabel_data;
return $tabel_data;

}


function kegiatan_paging($kodeuk) {
	
	drupal_set_message($kodeuk);
	
	$num = db_delete('kegiatanpage')
		->condition('kodeuk', $kodeuk)
	  	->execute();
	 
	$batas = 52;
	$arrbatas(0, 60, 52, 52, 52, 52, 52);

	$res_uk = db_query('select kodeuk, count(kodekeg) as jumlah from {kegiatanskpd} where jenis=2 and inaktif=0 group by kodeuk');
	foreach ($res_uk as $datauk) {		
		if ($datauk->jumlah>$batas) {
			
			$page = 1;
			$numkeg = 0;

			$res_keg = db_query('select kodepro,count(kodekeg) as jumlah from {kegiatanskpd} where kodeuk=:kodeuk group by kodepro order by kodepro', array(':kodeuk' => $datauk->kodeuk));
			foreach ($res_keg as $datakeg) {		
				
				$numkeg = $numkeg + $datakeg->jumlah;

				if ($numkeg >= $arrbatas[$page]) {
					$query = db_insert('kegiatanpage') // Table name no longer needs {}
						->fields(array(
							  'kodekeg' => $datakeg->kodepro,
							  'page' => $page,
							  'kodeuk' => $datauk->kodeuk,
						))
						->execute();	

					$page++ ;
					$numkeg = 0;
				}	
					
			}				
			
		}
	}
	

}

function kegiatan_paging1() {
	
	//drupal_set_message($bulan);
	
	$num = db_delete('kegiatanpage')
	  ->execute();
	
	$batas = 52;

	$res_uk = db_query('select kodeuk, count(kodekeg) as jumlah from {kegiatanskpd} where jenis=2 and inaktif=0 group by kodeuk');
	foreach ($res_uk as $datauk) {		
		if ($datauk->jumlah>$batas) {
			
			$page = 1;
			$i  = 0;

			$res_keg = db_query('select kodekeg from {kegiatanskpd} where kodeuk=:kodeuk order by kodepro,kodekeg', array(':kodeuk' => $datauk->kodeuk));
			foreach ($res_keg as $datakeg) {		
				
				$i++;

				$query = db_insert('kegiatanpage') // Table name no longer needs {}
					->fields(array(
						  'kodekeg' => $datakeg->kodekeg,
						  'page' => $page,
						  'kodeuk' => $datauk->kodeuk,
					))
					->execute();	
				
				if ($i == $batas) {
					$page++ ;
					$i = 0;
				}	
					
			}				
			
		}
	}
	

}


?>


