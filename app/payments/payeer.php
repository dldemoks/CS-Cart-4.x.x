<?php

if (!defined('BOOTSTRAP')) 
{ 
	die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) 
{
	$pp_response = array();
    
	if ($mode == 'notify' && !empty($_POST['m_orderid'])) 
	{
		if (isset($_POST['m_operation_id']) && isset($_POST['m_sign']))
		{
			$order_id = $_POST['m_orderid'];
			$order_info = fn_get_order_info($order_id);
	
			if (empty($processor_data)) 
			{
				$processor_data = fn_get_processor_data($order_info['payment_id']);
			}
	
			$m_key = $processor_data['processor_params']['m_key'];
			
			$arHash = array($_POST['m_operation_id'],
					$_POST['m_operation_ps'],
					$_POST['m_operation_date'],
					$_POST['m_operation_pay_date'],
					$_POST['m_shop'],
					$_POST['m_orderid'],
					$_POST['m_amount'],
					$_POST['m_curr'],
					$_POST['m_desc'],
					$_POST['m_status'],
					$m_key);
			$sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
			
			$log_text = 
				"--------------------------------------------------------\n".
				"operation id		".$_POST["m_operation_id"]."\n".
				"operation ps		".$_POST["m_operation_ps"]."\n".
				"operation date		".$_POST["m_operation_date"]."\n".
				"operation pay date	".$_POST["m_operation_pay_date"]."\n".
				"shop				".$_POST["m_shop"]."\n".
				"order id			".$_POST["m_orderid"]."\n".
				"amount				".$_POST["m_amount"]."\n".
				"currency			".$_POST["m_curr"]."\n".
				"description		".base64_decode($_POST["m_desc"])."\n".
				"status				".$_POST["m_status"]."\n".
				"sign				".$_POST["m_sign"]."\n\n";
				
			if (!empty($processor_data['processor_params']['pathlog']))
			{	
				file_put_contents($_SERVER['DOCUMENT_ROOT'] . $processor_data['processor_params']['pathlog'], $log_text, FILE_APPEND);
			}
			
			// проверка принадлежности ip списку доверенных ip
			$list_ip_str = str_replace(' ', '', $processor_data['processor_params']['ipfilter']);
			
			if (!empty($list_ip_str)) 
			{
				$list_ip = explode(',', $list_ip_str);
				$this_ip = $_SERVER['REMOTE_ADDR'];
				$this_ip_field = explode('.', $this_ip);
				$list_ip_field = array();
				$i = 0;
				$valid_ip = FALSE;
				foreach ($list_ip as $ip)
				{
					$ip_field[$i] = explode('.', $ip);
					if ((($this_ip_field[0] ==  $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
						(($this_ip_field[1] ==  $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
						(($this_ip_field[2] ==  $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
						(($this_ip_field[3] ==  $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
						{
							$valid_ip = TRUE;
							break;
						}
					$i++;
				}
			}
			else
			{
				$valid_ip = TRUE;
			}
			
			if ($_POST['m_sign'] == $sign_hash && $_POST['m_status'] == 'success' && $valid_ip)
			{
				if (fn_check_payment_script('payeer.php', $order_id)) 
				{
					$pp_response['order_status'] = 'C';
					$pp_response['reason_text'] = __('transaction_approved');
					$pp_response['transaction_id'] = $_POST['m_operation_id'];
					fn_finish_payment($order_id, $pp_response);
					exit($order_id . '|success');
				}
				else
				{
					$pp_response['order_status'] = 'D';
					$pp_response['reason_text'] = __('text_transaction_declined');
					$pp_response['transaction_id'] = $_POST['m_operation_id'];
					fn_finish_payment($order_id, $pp_response);
					exit($order_id . '|error');
				}
			}
			else
			{
				$pp_response['order_status'] = 'D';
				$pp_response['reason_text'] = __('text_transaction_declined');
				$pp_response['transaction_id'] = $_POST['m_operation_id'];
				fn_finish_payment($order_id, $pp_response);
				
				$to = $processor_data['processor_params']['emailerr'];
				$subject = __("payeer_mail_subject");
				$message = __("payeer_mail_msg1") . ":\n\n";
				
				if ($_POST["m_sign"] != $sign_hash)
				{
					$message .= __("payeer_mail_msg2") . "\n";
				}
				
				if ($_POST['m_status'] != "success")
				{
					$message .= __("payeer_mail_msg3") . "\n";
				}
				
				if (!$valid_ip)
				{
					$message .= __("payeer_mail_msg4") . "\n";
					$message .= __("payeer_mail_msg5") . $processor_data['processor_params']['ipfilter'] . "\n";
					$message .= __("payeer_mail_msg6") . $_SERVER['REMOTE_ADDR'] . "\n";
				}
				
				$message .= "\n" . $log_text;
				$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER'] . "\r\nContent-type: text/plain; charset=utf-8 \r\n";
				mail($to, $subject, $message, $headers);
	
				exit($order_id . '|error');
			}
		}
	}

    if ($mode == 'success' && !empty($_GET['m_orderid'])) 
	{
		if ($_GET['m_status'] == 'success') 
		{
			$pp_response['order_status'] = 'P';
			$pp_response['reason_text'] = __('transaction_approved');
			$pp_response['transaction_id'] = $_GET['m_operation_id'];
		}
	}
	elseif ($mode == 'fail' && !empty($_GET['m_orderid'])) 
	{
		if ($_GET['m_status'] == 'fail') 
		{
			$pp_response['order_status'] = 'F';
			$pp_response['reason_text'] = __('text_transaction_declined');
		}
	}
	
	$order_id = (int)$_GET['m_orderid'];
	$order_info = fn_get_order_info($order_id);
			
	if ($order_info['status'] == 'C' || $order_info['status'] == 'D')
	{
		$pp_response['order_status'] = $order_info['status'];
		fn_order_placement_routines('route', $order_id);
	}
	elseif (fn_check_payment_script('payeer.php', $order_id)) 
	{
		fn_order_placement_routines('route', $order_id);
        fn_finish_payment($order_id, $pp_response);
    }
} 
else 
{
	$payment_url = $processor_data['processor_params']['m_url'];
	$m_shop = $processor_data['processor_params']['m_shop'];
	$m_orderid = $order_id;
	$m_amount = fn_format_price($order_info['total'], $processor_data['processor_params']['currency']);
	$m_amount = number_format($m_amount, 2, '.', '');
	$m_curr = $processor_data['processor_params']['currency'];
	$m_desc = base64_encode($processor_data['processor_params']['m_desc']);
	$m_key = $processor_data['processor_params']['m_key'];

	$arHash = array(
		$m_shop,
		$m_orderid,
		$m_amount,
		$m_curr,
		$m_desc,
		$m_key
	);
	$sign = strtoupper(hash('sha256', implode(':', $arHash)));

	$post_data = array(
		'm_shop' => $m_shop,
		'm_orderid' => $m_orderid,
		'm_amount' => $m_amount,
		'm_curr' => $m_curr,
		'm_desc' => $m_desc,
		'm_sign' => $sign
	);

	fn_create_payment_form($payment_url, $post_data, 'Payeer', false);
}
exit;