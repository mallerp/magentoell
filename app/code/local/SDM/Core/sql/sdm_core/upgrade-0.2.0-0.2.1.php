<?php
/**
 * Separation Degrees Media
 *
 * SDM's core extension
 *
 * PHP Version 5
 *
 * @category  SDM
 * @package   SDM_Core
 * @author    Separation Degrees Media <magento@separationdegrees.com>
 * @copyright Copyright (c) 2015 Separation Degrees Media (http://www.separationdegrees.com)
 */

// Disable unneeded logins
$this->setConfigData('sociallogin/instalogin/is_active', 0);
$this->setConfigData('sociallogin/yalogin/is_active', 0);
