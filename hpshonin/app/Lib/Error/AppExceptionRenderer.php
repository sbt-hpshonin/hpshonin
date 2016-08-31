<?php
/**
 * 例外レンダラークラス
 */
App::uses('ExceptionRenderer', 'Error');
App::uses('NoDataException', 'Lib/Error');

class AppExceptionRenderer extends ExceptionRenderer {

	public function __construct(Exception $exception) {
		parent::__construct($exception);

		if ($exception instanceof NoDataException) {
			$this->method = 'errorNoData';
		}
		$this->controller->layout = 'error';
	}

	/**
	 * 取得データがなかった場合に発生するエラー画面のレンダリングを定義します。
	 *
	 * @param unknown $error
	 */
	public function errorNoData( $error ) {

		$this->controller->response->statusCode($error->getCode());
		$meta = array(
				'url' => $this->controller->request->here,
				'method' => $this->controller->request->method(),
		);
		$this->controller->set(array(
				'meta' => $meta,
				'error' => array(
						'message' => $error->getMessage(),
						'login_flag' => $error->login_flag,
						'code'	=> $error->getCode()
				),
				'error_exception' => $error,
				'_serialize' => array('meta', 'error')
		));
		$this->_outputMessage('no_data');
	}
}