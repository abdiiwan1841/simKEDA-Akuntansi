<?php
function jurnalspjjurnal_main($arg=NULL, $nama=NULL) {
	$qlike='';
	$limit = 10;
    
	if ($arg) {
		switch($arg) {
			case 'show':
				$qlike = " and lower(k.kegiatan) like lower('%%%s%%')";    
				break;
			case 'filter':
			
				//drupal_set_message('filter');
				//drupal_set_message(arg(5));
				
				$kodeuk = arg(2);
				$bulan = arg(3);
				$jenisdokumen = arg(4);
				$keyword = arg(5);

				break;
				
			case 'excel':
				break;

			default:
				//drupal_access_denied();
				break;
		}
	
	} else {
		if (isUserSKPD()) 
			$kodeuk = apbd_getuseruk();
		else {
			$kodeuk = $_SESSION["jurnalbelanja_kodeuk"];
			if ($kodeuk=='') $kodeuk = 'ZZ';
		}
		//$bulan = date('m');
		$bulan = $_SESSION["jurnalbelanja_bulan"];
		if ($bulan=='') $bulan = '0';
		
		$jenisdokumen = $_SESSION["jurnalbelanja_jenisdokumen"];
		if ($jenisdokumen=='') $jenisdokumen = 'ZZ';	

		$keyword = $_SESSION["jurnalbelanja_keyword"];
		if ($keyword=='') $keyword = 'ZZ';
		
	}
	
	if (isUserSKPD()) {
		$jurnalsuffix = 'uk';
	} else {
		$jurnalsuffix = '';		//$bulan = date('m');	
	}
	
	$output_form = drupal_get_form('jurnalspjjurnal_main_form');
	$header = array (
		array('data' => 'No','width' => '10px', 'valign'=>'top'),
		array('data' => '', 'width' => '10px', 'valign'=>'top'),
		array('data' => 'SKPD', 'field'=> 'namasingkat', 'valign'=>'top'),
		array('data' => 'No. SP2D','width' => '80px','field'=> 'refid', 'valign'=>'top'),
		array('data' => 'Tanggal', 'width' => '90px','field'=> 'tanggal', 'valign'=>'top'),
		array('data' => 'Kegiatan', 'field'=> 'kegiatan', 'valign'=>'top'),
		array('data' => 'Keperluan', 'field'=> 'keterangan', 'valign'=>'top'),
		array('data' => 'Jumlah', 'width' => '80px', 'field'=> 'total',  'valign'=>'top'),
		array('data' => '', 'width' => '50px', 'valign'=>'top'),
		array('data' => '', 'width' => '50px', 'valign'=>'top'),
		
	);
	
	$query = db_select('jurnal' . $jurnalsuffix, 'j')->extend('PagerDefault')->extend('TableSort');
	$query->innerJoin('unitkerja', 'u', 'j.kodeuk=u.kodeuk');
	$query->leftJoin('kegiatanskpd', 'k', 'j.kodekeg=k.kodekeg');
	if ($jenisdokumen !='ZZ') $query->condition('j.jenisdokumen', $jenisdokumen, '=');

	# get the desired fields from the database
	$query->fields('j', array('jurnalid', 'refid', 'kodekeg', 'kodeuk', 'nobukti', 'tanggal', 'keterangan', 'total', 'jenisdokumen'));
	$query->fields('u', array('namasingkat'));
	$query->fields('k', array('kegiatan'));
	
	//keyword
	if ($keyword!='ZZ') { 
		$db_or = db_or();
		$db_or->condition('j.keterangan', '%' . db_like($keyword) . '%', 'LIKE');	
		$db_or->condition('j.nobukti', '%' . db_like($keyword) . '%', 'LIKE');	
		$db_or->condition('j.nobuktilain', '%' . db_like($keyword) . '%', 'LIKE');	
		$query->condition($db_or);	
	}
	
	if ($kodeuk =='ZZ') {
		global $user;
		$username = $user->name;		
		
		$query->innerJoin('userskpd', 'us', 'j.kodeuk=us.kodeuk');
		$query->condition('us.username', $username, '=');
	
	} else {
		$query->condition('j.kodeuk', $kodeuk, '=');
	}
	if ($bulan !='0') $query->where('EXTRACT(MONTH FROM j.tanggal) = :month', array('month' => $bulan));
	
	//HANYA SELAIN`
	$query->condition('j.jenis', 'spj', '=');
	
	$query->orderByHeader($header);
	$query->orderBy('j.tanggal', 'ASC');
	$query->limit($limit);
		
	dpq($query);
	//drupal_set_message($jurnalsuffix);
	
	# execute the query
	$results = $query->execute();
		
	# build the table fields
	$no=0;

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$no = $page * $limit;
	} else {
		$no = 0;
	} 

	$rows = array();
	foreach ($results as $data) {
		$no++;  
		
		if ($data->jenisdokumen=='1')
			$kegiatan = 'Ganti Uang';
		elseif ($data->jenisdokumen=='5')
			$kegiatan = 'GU Nihil';
		elseif ($data->jenisdokumen=='7')
			$kegiatan = 'TU Nihil';
		else
			$kegiatan = $data->kegiatan;
		$rows[] = array(
						array('data' => $no, 'align' => 'right', 'valign'=>'top'),
						array('data' => apbd_icon_jurnal_sudah(),'align' => 'right', 'valign'=>'top'),
						array('data' => $data->namasingkat,  'align' => 'left', 'valign'=>'top'),
						array('data' => $data->nobukti, 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_format_tanggal_pendek($data->tanggal),  'align' => 'center', 'valign'=>'top'),
						array('data' => $kegiatan, 'align' => 'left', 'valign'=>'top'),
						array('data' => $data->keterangan, 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data->total),'align' => 'right', 'valign'=>'top'),
						apbd_button_jurnal('jurnalspjjurnal/jurnaledit/' . $data->jurnalid),
						apbd_button_esp2d($data->refid),
						//"<a href=\'?q=jurnal/edit/'>" . 'Register' . '</a>',
						
					);
	}
	
	
	$output = theme('table', array('header' => $header, 'rows' => $rows ));
	$output .= theme('pager');

	return drupal_render($output_form) . $output;
	
}


function getData($kodeuk,$bulan,$jenisdokumen,$keyword){

}

function jurnalspjjurnal_main_form_submit($form, &$form_state) {
	$kodeuk = $form_state['values']['skpd'];
	
	if($form_state['clicked_button']['#value'] == $form_state['values']['submit']) {
		$bulan = $form_state['values']['bulan'];
		$jenisdokumen = $form_state['values']['jenisdokumen'];
		$keyword = $form_state['values']['keyword'];
	
	} else {
		$bulan = '0';
		$jenisdokumen = 'ZZ';
		$keyword = '';
	}
	
	$_SESSION["jurnalbelanja_kodeuk"] = $kodeuk;
	$_SESSION["jurnalbelanja_bulan"] = $bulan;
	$_SESSION["jurnalbelanja_jenisdokumen"] = $jenisdokumen;
	$_SESSION["jurnalbelanja_keyword"] = $keyword;
	
	$uri = 'jurnalspjjurnal/filter/' . $kodeuk . '/' . $bulan . '/' . $jenisdokumen . '/' . $keyword;
	drupal_goto($uri);
	
}


function jurnalspjjurnal_main_form($form, &$form_state) {

	
	if(arg(2)!=null){
		
		$kodeuk = arg(2);
		$bulan=arg(3);
		$jenisdokumen = arg(4);
		$keyword = arg(5);

	
	
	} else {
		if (isUserSKPD()) 
			$kodeuk = apbd_getuseruk();
		else {
			$kodeuk = $_SESSION["jurnalbelanja_kodeuk"];
			if ($kodeuk=='') $kodeuk = 'ZZ';
		}
		//$bulan = date('m');
		$bulan = $_SESSION["jurnalbelanja_bulan"];
		if ($bulan=='') $bulan = '0';
		
		$jenisdokumen = $_SESSION["jurnalbelanja_jenisdokumen"];
		if ($jenisdokumen=='') $jenisdokumen = 'ZZ';	

		$keyword = $_SESSION["jurnalbelanja_keyword"];
		//if ($keyword=='') $keyword = 'ZZ';
		
	}
 
	$form['formdata'] = array (
		'#type' => 'fieldset',
		'#title'=>  'PILIHAN DATA',
		//'#title'=>  '<p>PILIHAN DATA</p>' . '<em><small class="text-info pull-right">klik disini utk menampilkan/menyembunyikan pilihan data</small></em>',
		//'#attributes' => array('class' => array('container-inline')),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,        
	);		
	
	if (isUserSKPD()) {
		$form['formdata']['skpd'] = array(
			'#type' => 'hidden',
			'#title' =>  t('SKPD'),
			'#default_value' => $kodeuk,
		);
		
	} else {

		global $user;
		$username = $user->name;		
	
		$option_skpd['ZZ'] = 'SELURUH SKPD';	
		
		$result = db_query('SELECT unitkerja.kodeuk, unitkerja.namasingkat FROM unitkerja INNER JOIN userskpd ON unitkerja.kodeuk=userskpd.kodeuk WHERE userskpd.username=:username ORDER BY unitkerja.namasingkat', array(':username' => $username));	
		while($row = $result->fetchObject()){
			$option_skpd[$row->kodeuk] = $row->namasingkat; 
		}
		 
		$form['formdata']['skpd'] = array(
			'#type' => 'select',
			'#title' =>  t('SKPD'),
			// The entire enclosing div created here gets replaced when dropdown_first
			// is changed.
			'#prefix' => '<div id="skpd-replace">',
			'#suffix' => '</div>',
			// When the form is rebuilt during ajax processing, the $selected variable
			// will now have the new value and so the options will change.
			'#options' => $option_skpd,
			//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,
			'#default_value' => $kodeuk,
		);
	}	
	
	//BULAN
	$option_bulan =array('Setahun', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
	$form['formdata']['bulan'] = array(
		'#type' => 'select',
		'#title' =>  t('Bulan'),
		// The entire enclosing div created here gets replaced when dropdown_first
		// is changed.
		'#options' => $option_bulan,
		//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,
		'#default_value' =>$bulan,
	);

	//JENIS DOKUMEN
	$opt_jenisdokumen['ZZ'] ='SEMUA';
	$opt_jenisdokumen['1'] = 'GANTI UANG (GU) PERSEDIAAN';
	$opt_jenisdokumen['3'] = 'LS GAJI';	
	$opt_jenisdokumen['4'] = 'LS BARANG DAN JASA';	
	$opt_jenisdokumen['5'] = 'GU NIHIL';	
	$opt_jenisdokumen['7'] = 'TU NIHIL';	
	$form['formdata']['jenisdokumen'] = array(
		'#type' => 'select',
		'#title' =>  t('Dokumen'),
		'#options' => $opt_jenisdokumen,
		//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,
		'#default_value' => $jenisdokumen,
	);	
	

	$form['formdata']['keyword'] = array(
		'#type' => 'textfield',
		'#title' =>  t('Kata Kunci'),
		'#description' =>  t('Kata kunci untuk mencari S2PD, bisa nama kegiatan, keperluan, atau nama penerima/pihak ketiga'),
		'#default_value' => $keyword, 
	);	
	
	//align-justify
	$form['formdata']['submit']= array(
		'#type' => 'submit',
		'#value' => '<span class="glyphicon glyphicon-align-justify" aria-hidden="true"></span> Tampilkan',
		'#attributes' => array('class' => array('btn btn-success btn-sm')),
	);
	$form['formdata']['reset']= array(
		'#type' => 'submit',
		'#value' => '<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> Reset',
		'#attributes' => array('class' => array('btn btn-success btn-sm')),
	);	
	return $form;
}



?>
