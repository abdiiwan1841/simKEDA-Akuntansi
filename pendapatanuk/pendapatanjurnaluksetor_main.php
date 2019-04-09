<?php
function pendapatanjurnaluksetor_main($arg=NULL, $nama=NULL) {
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
				
				$kodeuk = apbd_getuseruk();
				$bulan = arg(3);
				$hari = arg(4);
				$keyword = arg(5);

				break;
				
			case 'excel':
				break;

			default:
				//drupal_access_denied();
				break;
		}
		
	}  else {
		if (isUserSKPD()) 
			$kodeuk = apbd_getuseruk();
		else {
			$kodeuk = $_SESSION["pendapatanjurnaluksetor_kodeuk"];
			if ($kodeuk=='') $kodeuk = 'ZZ';
		}
		//$bulan = date('m');
		$bulan = $_SESSION["pendapatanjurnaluksetor_bulan"];
		if ($bulan=='') $bulan = "date('n')";
		
		$hari = $_SESSION["pendapatanjurnaluksetor_hari"];
		if ($hari=='') $hari = '0';
		
		$keyword = $_SESSION["pendapatanjurnaluksetor_keyword"];
		//if ($keyword=='') $keyword = ' ';
		
		/*
		$sp2dok = $_SESSION["sp2d_gaji_sp2dok"];
		if ($sp2dok=='') $sp2dok = 'ZZ';

		$jenisgaji = $_SESSION["sp2d_gaji_jenisgaji"];
		if ($jenisgaji=='') $jenisgaji = 'ZZ';
		*/
	}
	
	if ($keyword == '') $keyword = 'ZZ';
	
	//drupal_set_message($keyword);
	//drupal_set_message($jenisdokumen);
	
	//drupal_set_message(apbd_getkodejurnal('90'));
	
	$output_form = drupal_get_form('pendapatanjurnaluksetor_main_form');
	$header = array (
		array('data' => 'No','width' => '10px', 'valign'=>'top'),
		array('data' => '', 'width' => '10px', 'valign'=>'top'),
		array('data' => 'SKPD', 'field'=> 'namasingkat', 'valign'=>'top'),
		array('data' => 'Tanggal', 'width' => '90px','field'=> 'tanggal', 'valign'=>'top'),
		array('data' => 'Keterangan', 'field'=> 'keterangan', 'valign'=>'top'),
		array('data' => 'Jumlah', 'width' => '80px', 'field'=> 'total',  'valign'=>'top'),
		array('data' => '', 'width' => '60px', 'valign'=>'top'),
		
	);
	

	$query = db_select('jurnaluk', 'j')->extend('PagerDefault')->extend('TableSort');
	$query->innerJoin('unitkerja', 'u', 'j.kodeuk=u.kodeuk');

	# get the desired fields from the database
	$query->fields('j', array('jurnalid', 'refid', 'kodeuk', 'nobukti', 'tanggal', 'keterangan', 'total'));
	$query->fields('u', array('namasingkat'));
	
	//keyword
	if ($keyword!='ZZ') {
		$db_or = db_or();
		$db_or->condition('j.keterangan', '%' . db_like($keyword) . '%', 'LIKE');	
		$db_or->condition('j.nobukti', '%' . db_like($keyword) . '%', 'LIKE');	
		$db_or->condition('j.nobuktilain', '%' . db_like($keyword) . '%', 'LIKE');	
		$query->condition($db_or);	
	}
	
	if ($kodeuk !='ZZ') $query->condition('j.kodeuk', $kodeuk, '=');
	if ($bulan !='0') $query->where('EXTRACT(MONTH FROM j.tanggal) = :month', array('month' => $bulan));
	if ($hari !='0') $query->where('EXTRACT(DAY FROM j.tanggal) = :day', array('day' => $hari));
	
	//HANYA SELAIN`
	$query->condition('j.jenis', 'pad-out', '=');
	
	$query->orderByHeader($header);
	$query->orderBy('j.tanggal', 'DESC');
	$query->limit($limit);
		
	//dpq($query);
	
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
		
		$terjurnal = apbd_icon_jurnal_sudah();
		$editlink = apbd_button_jurnal('pendapatansetor/edit/' . $data->jurnalid);
		
		$rows[] = array(
						array('data' => $no, 'align' => 'right', 'valign'=>'top'),
						array('data' => $terjurnal,'align' => 'right', 'valign'=>'top'),
						array('data' => $data->namasingkat,  'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_format_tanggal_pendek($data->tanggal),  'align' => 'center', 'valign'=>'top'),
						array('data' => $data->keterangan, 'align' => 'left', 'valign'=>'top'),
						array('data' => apbd_fn($data->total),'align' => 'right', 'valign'=>'top'),
						$editlink,
						//"<a href=\'?q=jurnal/edit/'>" . 'Register' . '</a>',
						
					);
	}
	
	
	//BUTTON
	//$btn = apbd_button_baru('/pendapatansetor');
	//$btn = "&nbsp;" . apbd_button_print('');
	//$btn .= "&nbsp;" . apbd_button_excel('');	
	
	
	$output = theme('table', array('header' => $header, 'rows' => $rows ));
	$output .= theme('pager');
	/*
	if(arg(7)=='pdf'){
		$output=getData($kodeuk,$bulan,$jenisdokumen,$keyword);
		print_pdf_l($output);
		
	}
	else{
		return drupal_render($output_form) . $btn . $output . $btn;
	}
	*/
	return drupal_render($output_form) . $output;

}


function getData($kodeuk,$bulan,$jenisdokumen,$keyword){
	

}

function pendapatanjurnaluksetor_main_form_submit($form, &$form_state) {
	$kodeuk = $form_state['values']['kodeuk'];
	
	if($form_state['clicked_button']['#value'] == $form_state['values']['submit']) {
	
		$bulan = $form_state['values']['bulan'];
		$hari = $form_state['values']['hari'];
		$keyword = $form_state['values']['keyword'];
	} else {
		$bulan = '0';
		$hari = '0';
		$keyword = '';
		
	}	 
	$_SESSION["pendapatanjurnaluksetor_kodeuk"] = $kodeuk;
	$_SESSION["pendapatanjurnaluksetor_bulan"] = $bulan;
	$_SESSION["pendapatanjurnaluksetor_hari"] = $hari;
	$_SESSION["pendapatanjurnaluksetor_keyword"] = $keyword;
	
	$uri = 'pendapatanjurnaluksetor/filter/' . $kodeuk . '/' . $bulan . '/' . $hari . '/' . $keyword;
	drupal_goto($uri);
	
}


function pendapatanjurnaluksetor_main_form($form, &$form_state) {
	
	if(arg(2)!=null){
		
		$kodeuk = arg(2);
		$bulan=arg(3);
		$hari=arg(4);
		$keyword = arg(5);

	}  else {
		if (isUserSKPD()) 
			$kodeuk = apbd_getuseruk();
		else {
			$kodeuk = $_SESSION["pendapatanjurnaluksetor_kodeuk"];
			if ($kodeuk=='') $kodeuk = 'ZZ';
		}
		//$bulan = date('m');
		$bulan = $_SESSION["pendapatanjurnaluksetor_bulan"];
		if ($bulan=='') $bulan = "date('n')";
		
		$hari = $_SESSION["pendapatanjurnaluksetor_hari"];
		if ($hari=='') $hari = '0';
		
		$keyword = $_SESSION["pendapatanjurnaluksetor_keyword"];
		//if ($keyword=='') $keyword = ' ';
		
		/*
		$sp2dok = $_SESSION["sp2d_gaji_sp2dok"];
		if ($sp2dok=='') $sp2dok = 'ZZ';

		$jenisgaji = $_SESSION["sp2d_gaji_jenisgaji"];
		if ($jenisgaji=='') $jenisgaji = 'ZZ';
		*/
	}
 
	$form['formdata'] = array (
		'#type' => 'fieldset',
		'#title'=>  'PILIHAN DATA',
		//'#title'=>  '<p>PILIHAN DATA</p>' . '<em><small class="text-info pull-right">klik disini utk menampilkan/menyembunyikan pilihan data</small></em>',
		//'#attributes' => array('class' => array('container-inline')),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,        
	);		
	
	//SKPD
	$form['formdata']['kodeuk'] = array(
		'#type' => 'hidden',
		'#default_value' => $kodeuk,
	);
	
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

	//HARI
	$option_hari =array('Sebulan', '1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30', '31');
	$form['formdata']['hari'] = array(
		'#type' => 'select',
		'#title' =>  t('Tanggal'),
		// The entire enclosing div created here gets replaced when dropdown_first
		// is changed.
		'#options' => $option_hari,
		//'#default_value' => isset($form_state['values']['skpd']) ? $form_state['values']['skpd'] : $kodeuk,
		'#default_value' =>$hari,
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
