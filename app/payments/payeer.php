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
			$err = false;
			$message = '';
			$order_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($_POST['m_orderid'], 0, 32));
			$order_info = fn_get_order_info($order_id);

			if (empty($processor_data)) 
			{
				$processor_data = fn_get_processor_data($order_info['payment_id']);
			}
			
			// запись логов
			
			$log_text = 
			"--------------------------------------------------------\n" .
			"operation id		" . $_POST['m_operation_id'] . "\n" .
			"operation ps		" . $_POST['m_operation_ps'] . "\n" .
			"operation date		" . $_POST['m_operation_date'] . "\n" .
			"operation pay date	" . $_POST['m_operation_pay_date'] . "\n" .
			"shop				" . $_POST['m_shop'] . "\n" .
			"order id			" . $_POST['m_orderid'] . "\n" .
			"amount				" . $_POST['m_amount'] . "\n" .
			"currency			" . $_POST['m_curr'] . "\n" .
			"description		" . base64_decode($_POST['m_desc']) . "\n" .
			"status				" . $_POST['m_status'] . "\n" .
			"sign				" . $_POST['m_sign'] . "\n\n";
			
			$log_file = $processor_data['processor_params']['pathlog'];
			
			if (!empty($log_file))
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'] . $log_file, $log_text, FILE_APPEND);
			}
			
			// проверка цифровой подписи и ip

			$sign_hash = strtoupper(hash('sha256', implode(":", array(
				$_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$processor_data['processor_params']['m_key']
			))));
			
			$valid_ip = true;
			$sIP = str_replace(' ', '', $processor_data['processor_params']['ipfilter']);
			
			if (!empty($sIP))
			{
				$arrIP = explode('.', $_SERVER['REMOTE_ADDR']);
				if (!preg_match('/(^|,)(' . $arrIP[0] . '|\*{1})(\.)' .
				'(' . $arrIP[1] . '|\*{1})(\.)' .
				'(' . $arrIP[2] . '|\*{1})(\.)' .
				'(' . $arrIP[3] . '|\*{1})($|,)/', $sIP))
				{
					$valid_ip = false;
				}
			}
			
			if (!$valid_ip)
			{
				$message .= __("payeer_mail_msg4") . "\n" .
				__("payeer_mail_msg5") . $sIP . "\n" .
				__("payeer_mail_msg6") . $_SERVER['REMOTE_ADDR'] . "\n";
				$err = true;
			}

			if ($_POST['m_sign'] != $sign_hash)
			{
				$message .= __("payeer_mail_msg2") . "\n";
				$err = true;
			}
			
			if (!$err)
			{
				$order_curr = ($processor_data['processor_params']['currency'] == 'RUR') ? 'RUB' : $processor_data['processor_params']['currency'];
				$order_amount = number_format($order_info['total'], 2, '.', '');
				
				// проверка суммы и валюты
			
				if ($_POST['m_amount'] != $order_amount)
				{
					$message .= __("payeer_mail_msg7") . "\n";
					$err = true;
				}

				if ($_POST['m_curr'] != $order_curr)
				{
					$message .= __("payeer_mail_msg8") . "\n";
					$err = true;
				}
				
				// проверка статуса
				
				if (!$err)
				{
					switch ($_POST['m_status'])
					{
						case 'success':
							if (fn_check_payment_script('payeer.php', $order_id)) 
							{
								$pp_response['order_status'] = 'C';
								$pp_response['reason_text'] = __('transaction_approved');
								$pp_response['transaction_id'] = $_POST['m_operation_id'];
								fn_finish_payment($order_id, $pp_response);
							}
							break;
							
						default:
							$message .= __("payeer_mail_msg3") . "\n";
							$pp_response['order_status'] = 'D';
							$pp_response['reason_text'] = __('text_transaction_declined');
							$pp_response['transaction_id'] = $_POST['m_operation_id'];
							fn_finish_payment($order_id, $pp_response);
							$err = true;
							break;
					}
				}
			}
			
			if ($err)
			{
				$to = $processor_data['processor_params']['emailerr'];

				if (!empty($to))
				{
					$message = __("payeer_mail_msg1") . ":\n\n" . $message . "\n" . $log_text;
					$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
					"Content-type: text/plain; charset=utf-8 \r\n";
					mail($to, __("payeer_mail_subject"), $message, $headers);
				}
				
				exit($order_id . '|error');
			}
			else
			{
				exit($order_id . '|success');
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
	
	$order_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($_GET['m_orderid'], 0, 32));
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
	$m_desc = base64_encode($order_info['notes']);
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

	fn_create_payment_form($payment_url, $post_data, 'Payeer', true, 'get');
}
exit;