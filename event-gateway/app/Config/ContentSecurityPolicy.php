<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Default Content Security Policy settings.
 */
class ContentSecurityPolicy extends BaseConfig
{
    public bool $reportOnly = false;
    public ?string $reportURI = null;
    public bool $upgradeInsecureRequests = false;

    /**
     * @var list<string>|string|null
     */
    public $defaultSrc;

    /**
     * @var list<string>|string
     */
    public $scriptSrc = 'self';

    /**
     * @var list<string>|string
     */
    public $styleSrc = 'self';

    /**
     * @var list<string>|string
     */
    public $imageSrc = 'self';

    /**
     * @var list<string>|string|null
     */
    public $baseURI;

    /**
     * @var list<string>|string
     */
    public $childSrc = 'self';

    /**
     * @var list<string>|string
     */
    public $connectSrc = 'self';

    /**
     * @var list<string>|string
     */
    public $fontSrc;

    /**
     * @var list<string>|string
     */
    public $formAction = 'self';

    /**
     * @var list<string>|string|null
     */
    public $frameAncestors;

    /**
     * @var list<string>|string|null
     */
    public $frameSrc;

    /**
     * @var list<string>|string|null
     */
    public $mediaSrc;

    /**
     * @var list<string>|string
     */
    public $objectSrc = 'self';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc;

    /**
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * @var list<string>|string|null
     */
    public $sandbox;

    public string $styleNonceTag = '{csp-style-nonce}';
    public string $scriptNonceTag = '{csp-script-nonce}';
    public bool $autoNonce = true;
}
