<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Terminal42\ContaoDamIntegrator\Security\Permissions;

abstract class AbstractIntegration implements IntegrationInterface, ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;

    abstract public static function getKey(): string;

    public function supportsPicker(PickerConfig $pickerConfig): bool
    {
        return $this->isIntegrationEnabled() && 'file' === $pickerConfig->getContext();
    }

    protected function logError(string $message, \Throwable|null $exception = null): void
    {
        $context = null === $exception ? [] : ['exception' => $exception];

        $this->logger()->error($message, $context);
    }

    /**
     * @return array<string>|null Null if all are allowed, an array of restricted extensions
     */
    protected function getAllowedExtensionsFromPickerConfig(PickerConfig $pickerConfig): array|null
    {
        $extensions = $pickerConfig->getExtra('extensions');

        if (null === $extensions) {
            return null;
        }

        return array_unique(array_filter(StringUtil::trimsplit(',', $extensions)));
    }

    protected function isIntegrationEnabled(): bool
    {
        return $this->security()->isGranted(Permissions::USER_CAN_ACCESS_INTEGRATION, static::getKey());
    }

    protected function getTargetPath(string $name, string $targetDir, bool $replaceExisting, string|null $extension = null): string
    {
        $name = trim($name);

        if (null === $extension) {
            $extension = Path::getExtension($name);
            if ('' === $extension) {
                $extension = 'unknown';
            }
        }

        $extension = mb_strtolower(trim($extension, "\n\r\t\v\0."));
        $subfolder = trim($targetDir, '/').'/';

        if (mb_strlen($name) >= 2) {
            $subfolder .= mb_strtolower(mb_substr($name, 0, 1))
                .mb_strtolower(mb_substr($name, 1, 1))
                .'/';
        }

        $path = $subfolder.$name.'.'.$extension;

        // Check if already exists and increase index until we have a valid file name (if enabled
        $index = 1;

        while (!$replaceExisting && $this->virtualFilesystem()->fileExists($path)) {
            $path = $subfolder.$name.'_'.$index.'.'.$extension;
            ++$index;
        }

        return $path;
    }

    #[SubscribedService]
    protected function logger(): LoggerInterface
    {
        return $this->container->get(__FUNCTION__);
    }

    #[SubscribedService]
    protected function virtualFilesystem(): VirtualFilesystemInterface
    {
        return $this->container->get(__FUNCTION__);
    }

    #[SubscribedService]
    protected function security(): Security
    {
        return $this->container->get(__FUNCTION__);
    }
}
