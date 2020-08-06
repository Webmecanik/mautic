<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers;

interface TransformerInterface
{
    const DNC_TYPE       = 'dnc';

    const ALPHANUMERIQUE = 'ALPHANUMERIQUE';

    const DATE           = 'DATE';

    const BOOLEEN        = 'BOOLEEN';

    const NUMERIQUE      = 'NUMERIQUE';

    const ZONETEXTE      = 'ZONETEXTE';

    const TELEPHONE      = 'TELEPHONE';

    const ENTIER         = 'ENTIER';

    // Owner of contact
    const IDUTILISATEUR         = 'IDUTILISATEUR';

    public function transform($type, $value);
}
