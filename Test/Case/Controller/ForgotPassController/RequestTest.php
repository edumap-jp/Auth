<?php
/**
 * ForgotPassController::request()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * ForgotPassController::request()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\ForgotPassController
 */
class ForgotPassControllerRequestTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.site_setting4auth',
		'plugin.auth.user4auth',
		'plugin.user_attributes.user_attribute_setting4test',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'auth';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'forgot_pass';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		//ログイン
		TestAuthGeneral::login($this);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		//ログアウト
		TestAuthGeneral::logout($this);

		parent::tearDown();
	}

/**
 * request()アクションのGetリクエストテスト
 *
 * @return void
 */
	public function testRequestGet() {
		//テスト実行
		$this->_testGetAction(array('action' => 'request'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->assertEquals($this->controller->request->data['ForgotPass']['email'], null);
		$this->assertInput('input', 'data[ForgotPass][email]', null, $this->view);
	}

/**
 * request()アクションのGetリクエストテスト
 *
 * @return void
 */
	public function testRequestGetWithQueryEmail() {
		//テスト実行
		$this->_testGetAction(
			array('action' => 'request', '?' => array('email' => 'test@test.aa.aa')),
			array('method' => 'assertNotEmpty'), null, 'view'
		);

		//チェック
		$this->assertEquals($this->controller->request->data['ForgotPass']['email'], 'test@test.aa.aa');
		$this->assertInput('input', 'data[ForgotPass][email]', 'test@test.aa.aa', $this->view);
	}

/**
 * POSTリクエストデータ生成
 *
 * @return array リクエストデータ
 */
	private function __data($email = 'system_admin_1@exapmle.com') {
		$data = array(
			'ForgotPass' => array('email' => $email)
		);

		return $data;
	}

/**
 * POSTリクエストテストの事前準備
 *
 * @param array $forgotPass セッションに登録するユーザデータ
 * @param bool $success 成否
 * @return void
 */
	private function __prepareRequestPost($forgotPass, $success) {
		//テストデータ
		$this->_mockForReturn('Auth.ForgotPass', 'validateRequest', array('ForgotPass' => $forgotPass));

		$this->controller->mail = $this->getMock(
			'NetCommonsMail',
			array('sendMailDirect', 'initPlugin', 'to', 'setFrom'),
			array(), '', false
		);
		$this->controller->mail->mailAssignTag = $this->getMock(
			'NetCommonsMail',
			array('setFixedPhraseSubject', 'setFixedPhraseBody', 'initPlugin', 'assignTags'),
			array(), '', false
		);

		if ($success === true) {
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('setFixedPhraseSubject')
				->with('ForgotPass.issue_mail_subject 2');
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('setFixedPhraseBody')
				->with('ForgotPass.issue_mail_body 2');
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('assignTags')
				->with(array('X-AUTHORIZATION_KEY' => 'authorization_test'));
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('initPlugin')
				->with('2');

			$this->controller->mail
				->expects($this->once())->method('initPlugin')
				->with('2');
			$this->controller->mail
				->expects($this->once())->method('to')
				->with($forgotPass['email']);
			$this->controller->mail
				->expects($this->once())->method('setFrom')
				->with('2');
		} else {
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('setFixedPhraseSubject')
				->will($this->returnValue(false));
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('setFixedPhraseBody')
				->will($this->returnValue(false));
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('assignTags')
				->will($this->returnValue(false));
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('initPlugin')
				->will($this->returnValue(false));

			$this->controller->mail
				->expects($this->exactly(0))->method('initPlugin')
				->will($this->returnValue(false));
			$this->controller->mail
				->expects($this->exactly(0))->method('to')
				->will($this->returnValue(false));
			$this->controller->mail
				->expects($this->exactly(0))->method('setFrom')
				->will($this->returnValue(false));
		}
		$this->controller->Components->Session
			->expects($this->at(1))->method('write')
			->with('ForgotPass', $forgotPass);
	}

/**
 * request()アクションのPOSTリクエストテスト
 * - 正常
 *
 * @return void
 */
	public function testRequestPost() {
		//事前準備
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'Session' => array('write'),
			'NetCommons.NetCommons' => array('handleValidationError', 'setFlashNotification')
		)));

		$expected = array(
			'user_id' => '1',
			'username' => 'system_administrator',
			'handlename' => 'System Administrator',
			'authorization_key' => 'authorization_test',
			'email' => 'system_admin_1@exapmle.com'
		);
		$this->__prepareRequestPost($expected, true);

		$this->controller->mail
			->expects($this->once())->method('sendMailDirect')
			->will($this->returnValue(true));
		$this->controller->Components->NetCommons
			->expects($this->exactly(0))->method('handleValidationError')
			->will($this->returnValue(false));

		$this->controller->Components->NetCommons
			->expects($this->once())->method('setFlashNotification')
			->with(
				__d(
					'auth',
					'We have sent you the key to obtain a new password to your registered e-mail address.'
				),
				array('class' => 'success')
			);

		//テスト実行
		$this->_testPostAction('post', $this->__data(), array('action' => 'request'), null, 'view');

		//チェック
		$header = $this->controller->response->header();
		$pattern = '/auth/forgot_pass/confirm';
		$this->assertTextContains($pattern, $header['Location']);
	}

/**
 * request()アクションのPOSTリクエストテスト
 * - sendMailDirect()エラーテスト
 *
 * @return void
 */
	public function testRequestPostOnSendError() {
		//事前準備
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'Session' => array('write'),
			'NetCommons.NetCommons' => array('handleValidationError', 'setFlashNotification')
		)));

		$expected = array(
			'user_id' => '1',
			'username' => 'system_administrator',
			'handlename' => 'System Administrator',
			'authorization_key' => 'authorization_test',
			'email' => 'system_admin_1@exapmle.com'
		);
		$this->__prepareRequestPost($expected, true);

		$this->controller->mail
			->expects($this->once())->method('sendMailDirect')
			->will($this->returnValue(false));
		$this->controller->Components->NetCommons
			->expects($this->once())->method('handleValidationError')
			->with(array('SendMail Error'));

		$this->controller->Components->NetCommons
			->expects($this->exactly(0))->method('setFlashNotification')
			->will($this->returnValue(false));

		//テスト実行
		$this->_testPostAction('post', $this->__data(), array('action' => 'request'), null, 'view');
	}

/**
 * request()アクションのPOSTリクエストテスト
 * - メールアドレス不正テスト
 *
 * @return void
 */
	public function testRequestPostOnMailAddress() {
		//事前準備
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'Session' => array('write'),
			'NetCommons.NetCommons' => array('handleValidationError', 'setFlashNotification')
		)));

		$expected = array(
			'user_id' => '0',
			'username' => '',
			'handlename' => '',
			'authorization_key' => 'authorization_test',
			'email' => 'system_admin_1@exapmle.com'
		);
		$this->__prepareRequestPost($expected, false);

		$this->controller->mail
			->expects($this->exactly(0))->method('sendMailDirect')
			->will($this->returnValue(false));
		$this->controller->Components->NetCommons
			->expects($this->exactly(0))->method('handleValidationError')
			->will($this->returnValue(false));

		$this->controller->Components->NetCommons
			->expects($this->once())->method('setFlashNotification')
			->with(
				__d(
					'auth',
					'We have sent you the key to obtain a new password to your registered e-mail address.'
				),
				array('class' => 'success')
			);

		//テスト実行
		$this->_testPostAction('post', $this->__data(), array('action' => 'request'), null, 'view');
	}

/**
 * request()アクションのPOSTリクエストテスト
 * - validationError
 *
 * @return void
 */
	public function testRequestPostValidationError() {
		//事前準備
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'NetCommons.NetCommons' => array('handleValidationError', 'setFlashNotification')
		)));
		$this->controller->mail = $this->getMock(
			'NetCommonsMail',
			array('sendMailDirect', 'initPlugin', 'to', 'setFrom'),
			array(), '', false
		);

		$this->controller->mail
			->expects($this->exactly(0))->method('sendMailDirect')
			->will($this->returnValue(false));

		$this->controller->Components->NetCommons
			->expects($this->once())->method('handleValidationError')
			->with(array('email' => array(__d('net_commons', 'Please input %s.', __d('auth', 'email')))));

		//テスト実行
		$this->_testPostAction('post', $this->__data(''), array('action' => 'request'), null, 'view');

		//チェック
		$expected = '<div class="has-error"><div class="help-block">' .
						__d('net_commons', 'Please input %s.', __d('auth', 'email')) .
					'</div></div>';
		$this->assertTextContains($expected, $this->view);
	}

}
