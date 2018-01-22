<?php
class display
{
	public function __construct($state,$msg,$encode='',$time=true){
		$add='';
		if($state=='success'){
				$type = "alert-success";
				$title='Succès';
		}
		if($state=='error'){
				$type = "alert-danger";
				$title='Erreur';
		}
		if($state=='info'){
				$type = "alert-info";
				$title='Info';
		}
		if($state=='warning'){
				$type = "alert-warning";
				$title='Attention';
		}
		if($time){
			$add = '	<script>
				$(".'.$type.'").fadeTo(3000, 500).slideUp(500, function(){
						$(".'.$type.'").slideUp(500);
					});
				</script>';
		}
		$html= '<div class="alert '.$type.'" role="alert">
					 <button type="button" class="close" data-dismiss="alert" aria-label="Close">
	  				  <span aria-hidden="true">&times;</span>
	 				 </button>
			  		<h4 class="alert-heading result" >'.$title.'</h4>
			  		<p>'.$msg.'</p>
				</div>
			'.$add;
		if($encode != ''){
			$return = json_encode(array('status'=> $state, 'html'=>$html));
			echo $return;
		} else {
			echo $html;
		}

	}
}
