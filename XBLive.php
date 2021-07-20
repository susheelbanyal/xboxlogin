<?php
error_reporting(E_ALL | E_STRICT);
class XBLive {
	public $logoutRedirectUri = "";

	/*Microsoft Live Login URIs*/
	public $urlLogin = 'https://login.live.com';

	// /*XBOX Live Login URIs*/
	public $urlUserAuthenticate = 'https://user.auth.xboxlive.com/user/authenticate';
	public $urlXSTSAuthorize = 'https://xsts.auth.xboxlive.com/xsts/authorize';
	public $urlAuthRelyingParty = 'http://auth.xboxlive.com';
	public $urlXboxRelyingParty = 'http://xboxlive.com';
	public $urlDefaultSiteName = 'user.auth.xboxlive.com';

	// /*XBOX Live API URIs*/
	private $urlXBLiveApiProfile = "https://profile.xboxlive.com";
	public $defaultXboxLiveLoginHeaders = ["x-xbl-contract-version: 1", "Content-Type: application/json"];
	public $defaultXboxLiveApiQueryHeaders = ["x-xbl-contract-version: 2", "Content-Type: application/json"];
	public $defaultSandboxId = "RETAIL";


	public function __construct($config = []) {
		$this->config = $config;
		session_start();
	}

	public function getBaseAuthorizationUrl() {
		$authorize_url = $this->urlLogin . '/oauth20_authorize.srf';

		$parameters = [
			'client_id' => $this->config['client_id'],
			'response_type' => 'code',
			"approval_prompt" => "auto",
			'redirect_uri' => $this->config['redirect_uri'],
			'response_mode' => 'query',
			'scope' => implode(' ', $this->config['scope']),
			'state' => $this->config['state']
		];
		return $authorize_url . "?" . http_build_query($parameters);
	}

	public function GetAccessToken($options) {
		$token_url = $this->urlLogin . '/oauth20_token.srf';
		$data = [
			'grant_type' => 'authorization_code',
			'client_id' => $this->config['client_id'],
			'code' => $options['code'],
			'redirect_uri' => $this->config['redirect_uri'],
			'scope' => implode(' ', $this->config['scope']),
		];
		return $this->requestApi(http_build_query($data), $token_url, 'POST', []);
	}

	public function requestApi($data, $url, $method, $headers = []) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($method == 'GET') {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		$result = json_decode($result, true);
	
		curl_close($ch);
		if (isset($result['error'])) {
			return false;
		}
		return $result;
	}

	public function getXasuToken($token) {
		$headers = $this->defaultXboxLiveLoginHeaders;
		$body = [
			"RelyingParty" => $this->urlAuthRelyingParty,
			"TokenType" => "JWT",
			"Properties" => [
				"AuthMethod" => "RPS",
				"SiteName" => $this->urlDefaultSiteName,
				"RpsTicket" => "d=" . $token['access_token'] . "token_type=" . $token["token_type"] . "&expires_in=" . $token['expires_in'] . "&scope=" . $token["scope"] . "&user_id=" . $token["user_id"]
			]
		];
		$body = json_encode($body);
		return $this->requestApi($body, $this->urlUserAuthenticate, 'POST', $headers);
	}

	public function getXstsToken($xasuToken) {
		$headers = $this->defaultXboxLiveLoginHeaders;
		$body = [
			"RelyingParty" => $this->urlXboxRelyingParty,
			"TokenType" => "JWT",
			"Properties" => [
				"UserTokens" => array($xasuToken['Token']),
				"SandboxId" => $this->defaultSandboxId
			]
		];
		$body = json_encode($body);
		return $this->requestApi($body, $this->urlXSTSAuthorize, 'POST', $headers);
	}

	protected function getXboxLiveApiQueryHeaders($xstsToken) {
		array_push($this->defaultXboxLiveApiQueryHeaders, "Authorization: " . $xstsToken->getAuthorizationHeader());
		return $this->defaultXboxLiveApiQueryHeaders;
	}

	public function getLoggedUserProfile($xstsTokenArr) {
		$xstsToken = new XBLiveXstsToken($xstsTokenArr);
		$requestUrl = $this->urlXBLiveApiProfile . "/users/batch/profile/settings";
		$body = [
			"userIds" => [$xstsToken->getXstsXuid()],
			"settings" => ["GameDisplayName", "GameDisplayPicRaw", "Gamerscore", "Gamertag"]
		];
		$headers = $this->getXboxLiveApiQueryHeaders($xstsToken);

		$body = json_encode($body);
		return $this->requestApi($body, $requestUrl, 'POST', $headers);
	}

	public function getState() {
		return $this->config['state'];
	}
}

class XBLiveXstsToken {
	protected $issueInstant = null;
	protected $notAfter = null;
	protected $token = null;
	protected $displayClaims = null;

	public function __construct(array $options = []) {
		if (isset($options['IssueInstant'])) {
			$this->issueInstant = new \DateTime($options['IssueInstant']);
		}
		if (isset($options['NotAfter'])) {
			$this->notAfter = new \DateTime($options['NotAfter']);
		}
		if (isset($options['Token'])) {
			$this->token = $options['Token'];
		}
		if (isset($options['DisplayClaims'])) {
			$this->displayClaims = new XBLiveDisplayClaimXsts($options['DisplayClaims']["xui"]);
		}
	}

	public function getToken() {
		return $this->token;
	}

	public function getDisplayClaims() {
		return $this->displayClaims;
	}

	public function getXstsUserHash() {
		return $this->getDisplayClaims()->getXuiClaims()[0]->getUserHash();
	}

	public function getXstsXuid() {
		return $this->getDisplayClaims()->getXuiClaims()[0]->getXuid();
	}

	public function getAuthorizationHeader() {
		return 'XBL3.0 x=' . $this->getXstsUserHash() . ';' . $this->getToken();
	}
}

class XBLiveDisplayClaimXsts {
	protected $xui = array();

	public function __construct(array $options = []) {
		if (count($options) == 0)
			throw new UnexpectedValueException(
				'XBOX Live service has returned no xui claims'
			);

		for ($i = 0; $i < count($options); $i++) {
			if (array_key_exists("uhs", $options[$i]) && $options[$i]["uhs"] != "") {
				array_push($this->xui, new XBLiveXuiClaimXsts($options[$i]));
			} else {
				throw new UnexpectedValueException(
					'Invalid user hash inside XSTS token'
				);
			}
		}
	}

	public function getXuiClaims() {
		return $this->xui;
	}
}

class XBLiveXuiClaimXsts {
	protected $agg;
	protected $gtg;
	protected $prv;
	protected $xid;
	protected $uhs;

	public function __construct(array $options) {
		if (isset($options['agg'])) {
			$this->agg = $options['agg'];
		}
		if (isset($options['gtg'])) {
			$this->gtg = $options['gtg'];
		}
		if (isset($options['prv'])) {
			$this->prv = $options['prv'];
		}
		if (isset($options['xid'])) {
			$this->xid = $options['xid'];
		}
		if (isset($options['uhs'])) {
			$this->uhs = $options['uhs'];
		}
	}

	public function getAgeGroup() {
		return $this->agg;
	}

	public function getGamertag() {
		return $this->gtg;
	}

	public function getPrivileges() {
		return $this->prv;
	}

	public function getXuid() {
		return $this->xid;
	}

	public function getUserHash() {
		return $this->uhs;
	}
}
