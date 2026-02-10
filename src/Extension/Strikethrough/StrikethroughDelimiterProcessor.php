<?php

declare(strict_types=1);

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com> and uAfrica.com (http://uafrica.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\CommonMark\Extension\Strikethrough;

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\CacheableDelimiterProcessorInterface;
use League\CommonMark\Node\Inline\AbstractStringContainer;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;
use League\Config\Exception\InvalidConfigurationException;

final class StrikethroughDelimiterProcessor implements CacheableDelimiterProcessorInterface, ConfigurationAwareInterface
{
    public const DELIMITER_SINGLE = 'single';
    public const DELIMITER_DOUBLE = 'double';
    public const DELIMITER_BOTH   = 'both';

    private ConfigurationInterface $config;

    public function getOpeningCharacter(): string
    {
        return '~';
    }

    public function getClosingCharacter(): string
    {
        return '~';
    }

    public function getMinLength(): int
    {
        switch ($this->config->get('strikethrough/delimiter')) {
            case self::DELIMITER_SINGLE:
            case self::DELIMITER_BOTH:
                return 1;
            case self::DELIMITER_DOUBLE:
                return 2;
            default:
                throw new InvalidConfigurationException("Invalid configuration value for strikethrough/delimiter; expected 'single', 'double', or 'both'");
        }
    }

    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer): int
    {
        $length = $this->getMaxDelimiterLength();

        if ($opener->getLength() > $length && $closer->getLength() > $length) {
            return 0;
        }

        if ($opener->getLength() !== $closer->getLength()) {
            return 0;
        }

        // $opener and $closer are the same length so we just return one of them
        return $opener->getLength();
    }

    public function getMaxDelimiterLength(): int
    {
        switch ($this->config->get('strikethrough/delimiter')) {
            case self::DELIMITER_SINGLE:
                return 1;
            case self::DELIMITER_DOUBLE:
            case self::DELIMITER_BOTH:
                return 2;
            default:
                throw new InvalidConfigurationException("Invalid configuration value for strikethrough/delimiter; expected 'single', 'double', or 'both'");
        }
    }

    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse): void
    {
        $strikethrough = new Strikethrough(\str_repeat('~', $delimiterUse));

        $tmp = $opener->next();
        while ($tmp !== null && $tmp !== $closer) {
            $next = $tmp->next();
            $strikethrough->appendChild($tmp);
            $tmp = $next;
        }

        $opener->insertAfter($strikethrough);
    }

    public function getCacheKey(DelimiterInterface $closer): string
    {
        return '~' . $closer->getLength();
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
