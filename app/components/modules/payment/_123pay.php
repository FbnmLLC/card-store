<?php

namespace app\components\modules\payment;

use framework\request\Request;

/**
 * payment with 123PAY.IR
 *
 * @author    123PAY.IR <plugins@123pay.ir>
 * @since    1.0
 * @package    payment module
 * @copyright    GPLv3
 */
class _123pay extends Payment {
	public function request( $id, $au, $price, $module, $product ) {
		$merchant_id  = $module['merchant_id']['value'];
		$amount       = $this->getRial( $price );
		$callback_url = $this->getCallbackUrl( $au );
		$ch           = curl_init();
		curl_setopt( $ch, CURLOPT_URL, 'https://123pay.ir/api/v1/create/payment' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "merchant_id=$merchant_id&amount=$amount&callback_url=$callback_url" );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );
		curl_close( $ch );
		$result = json_decode( $response );
		if ( $result->status ) {
			$this->updateAu( $id, $result->RefNum );
			$this->redirect( $result->payment_url );
		} else {
			$this->setFlash( 'danger', $result->message );

			return false;
		}
	}

	public function verify( $id, $au, $price, $module, $product ) {
		if ( ! isset( $_REQUEST['State'] ) ) {
			$this->setFlash( 'danger', 'اطلاعات دریافتی از یک دو سه پی صحیح نیست' );

			return false;
		}
		$State  = $_REQUEST['State'];
		$RefNum = $_REQUEST['RefNum'];

		$merchant_id = $module['merchant_id']['value'];

		if ( $State == 'OK' ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, 'https://123pay.ir/api/v1/verify/payment' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, "merchant_id=$merchant_id&RefNum=$RefNum" );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$response = curl_exec( $ch );
			curl_close( $ch );
			$result = json_decode( $response );
			if ( $result->status ) {
				return array( 'au' => Request::getQuery( 'RefNum' ) );
			} else {
				$this->setFlash( 'danger', $result->message );
			}
		} else {
			$this->setFlash( 'danger', 'تراکنش ناموفق' );

			return false;
		}
	}

	public function fields() {
		return array(
			'merchant_id' => array(
				'label' => $this->lang()->getIndex( '_123pay', 'merchant_id' ),
				'value' => '',
			)
		);
	}
}

?>