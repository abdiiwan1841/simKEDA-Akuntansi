<?php
function pendapatansetor_batch_main($arg=NULL, $nama=NULL) {

	$output_form = drupal_get_form('pendapatansetor_batch_main_form');
	return drupal_render($output_form);// . $output;

}

function pendapatansetor_batch_main_form($form, &$form_state) {
	
	if (isUserSKPD()) {
		$kodeuk = apbd_getuseruk();
		
	} else {
		$kodeuk = '00';
	}	

 	
	db_set_active('pendapatan');

	$results = db_query('select id, tgl_keluar, keterangan, jumlah from q_antriansetorkeluar where kodeuk=:kodeuk order by tgl_keluar asc limit 5', array(':kodeuk'=>$kodeuk));
	
	$form['tbljurnal']= array(
		'#prefix' => '<table class="table table-hover"><tr><th width="10px">NO</th><th width="90px">TANGGAL</th><th>URAIAN</th><th>KETERANGAN</th><th width="80px">JUMLAH</th><th width="5px"></th></tr>',
		 '#suffix' => '</table>',
	);	
	$i = 0;
	
	foreach ($results as $data) {

		$i++; 
		
		$uraian= '';
		$res = db_query('select kodero, tanggal, uraian, jumlahmasuk from setor where idkeluar=:idkeluar', array(':idkeluar'=>$data->id));	
		foreach ($res as $dat) {
			$uraian .= $dat->kodero . '-' . $dat->uraian . ' ' . apbd_fd($dat->tanggal) . ', ' . apbd_fn($dat->jumlahmasuk) . '; ';
		}
		
	
		$form['tbljurnal']['setorid' . $i]= array(
				'#type' => 'value',
				'#value' => $data->id,
		); 
		$form['tbljurnal']['tanggal' . $i]= array(
				'#type' => 'value',
				'#value' => $data->tgl_keluar,
		);			
		$form['tbljurnal']['jumlah' . $i]= array(
				'#type' => 'value',
				'#value' => $data->jumlah,
		);				
		$form['tbljurnal']['uraian' . $i]= array(
				'#type' => 'value',
				'#value' => $uraian,
		);				

		$form['tbljurnal']['nomor' . $i]= array(
				'#prefix' => '<tr><td>',
				'#markup' => $i,
				//'#size' => 10,
				'#suffix' => '</td>',
		); 
		$form['tbljurnal']['tanggalview' . $i]= array(
			//'#type'         => 'textfield', 
			'#prefix' => '<td>',
			'#markup'=> apbd_format_tanggal_pendek($data->tgl_keluar), 
			'#suffix' => '</td>',
		); 
		$form['tbljurnal']['uraianview' . $i]= array(
			//'#type'         => 'textfield', 
			'#prefix' => '<td>',
			'#markup'=> $uraian, 
			'#suffix' => '</td>',
		); 
		$form['tbljurnal']['keterangan' . $i]= array(
			'#type'         => 'textfield', 
			'#prefix' => '<td>',
			'#default_value'=> $data->keterangan, 
			'#suffix' => '</td>',
		); 
		$form['tbljurnal']['jumlahview' . $i]= array(
			//'#type'         => 'textfield', 
			'#prefix' => '<td>',
			'#markup'=> '<p align="right">' . apbd_fn($data->jumlah) . '</p>' , 
			'#suffix' => '</td>',
		); 
		$form['tbljurnal']['jurnalkan' . $i]= array(
			'#type'         => 'checkbox', 
			'#default_value'=> true, 
			'#prefix' => '<td>',
			'#suffix' => '</td></tr>',
		);	

	}
	db_set_active();
	
		
	$form['kodeuk']= array(
		'#type' => 'value',
		'#value' => $kodeuk,
	);	
	$form['jumlahkegiatan']= array(
		'#type' => 'value',
		'#value' => $i,
	);	

	
	
	//FORM SUBMIT DECLARATION
	$form['formdata']['submit']= array(
		'#type' => 'submit',
		'#value' => '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Jurnalkan',
		'#attributes' => array('class' => array('btn btn-success btn-sm pull-right')),
		//'#suffix' => "&nbsp;<a href='" . $referer . "' class='btn btn-default btn-sm'><span class='glyphicon glyphicon-log-out' aria-hidden='true'></span>Tutup</a>",
	);
	
	return $form;
}

function pendapatansetor_batch_main_form_validate($form, &$form_state) {
		
}


function pendapatansetor_batch_main_form_submit($form, &$form_state) {
	$kodeuk = $form_state['values']['kodeuk'];
	$jumlahkegiatan = $form_state['values']['jumlahkegiatan'];

	for ($n=1; $n <= $jumlahkegiatan; $n++) {
		if ($form_state['values']['jurnalkan' . $n]) {
			
			$setorid = $form_state['values']['setorid' . $n];
			$tanggal = $form_state['values']['tanggal' . $n];
			$keterangan = $form_state['values']['keterangan' . $n];
			$keterangan = $keterangan . '; ' . $form_state['values']['uraian' . $n];
			$jumlah = $form_state['values']['jumlah' . $n];
			
			
			//drupal_set_message($kodeuk);			
			//drupal_set_message($setorid);
			//drupal_set_message($tanggal);
			//drupal_set_message($keterangan);
			//dr/upal_set_message($jumlah);
			//drupal_set_message($setorid);
			
			$res = jurnalkansetoran($kodeuk, $setorid, $setorid, $tanggal, $keterangan, $jumlah);
			if ($res) drupal_set_message('Penjurnalan setoran ke-'. $n . ' berhasil.');
		}
		
	}


}

function jurnalkansetoran($kodeuk, $setorid, $nobukti, $tanggal, $keterangan, $jumlah) {
	
	$jurnalid = apbd_getkodejurnal_uk($kodeuk);
	$query = db_insert('jurnaluk')
			->fields(array('jurnalid', 'refid', 'kodeuk', 'jenis', 'nobukti', 'nobuktilain', 'tanggal', 'keterangan', 'total'))
			->values(
				array(
					'jurnalid'=> $jurnalid,
					'refid' => $setorid,
					'kodeuk' => $kodeuk,
					'jenis' => 'pad-out',
					'nobukti' => $nobukti,
					'nobuktilain' => $nobukti,
					'tanggal' =>$tanggal,
					'keterangan' => $keterangan, 
					'total' => $jumlah,
				)
			);
	//drupal_set_message($query);		
	$res = $query->execute();
	
	//JURNAL ITEM APBD
	//1
	$query = db_insert('jurnalitemuk')
			->fields(array('jurnalid', 'nomor', 'kodero', 'debet'))
			->values(
				array(
					'jurnalid' => $jurnalid,
					'nomor' => 1,
					'kodero' => apbd_getKodeROAPBD(), //'11103001',
					'debet' => $jumlah,
				)
			); 
	$res = $query->execute();
	
	//2. 
	$query = db_insert('jurnalitemuk')
			->fields(array('jurnalid', 'nomor', 'kodero', 'kredit'))
			->values(
				array(
					'jurnalid'=> $jurnalid,
					'nomor' => 2,
					'kodero' => '11103001',
					'kredit' => $jumlah,
				)
			);
	$res = $query->execute();
	 

	//PBP
	db_set_active('pendapatan');
	$query = db_update('setoridmaster')
	->fields(
			array(
				'jurnalsudah' => 1,
				'jurnalid' => $jurnalid,
			)
		);
	$query->condition('id', $setorid, '=');
	$res = $query->execute();
	db_set_active();
	
	return true;
}

?>
