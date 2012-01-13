<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use Propel\Generator\Exception\RuntimeException;
use Propel\Generator\Exception\InvalidArgumentException;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    const DEFAULT_INPUT_DIRECTORY   = '.';

    const DEFAULT_PLATFORM          = 'MysqlPlatform';

    protected $connections = array();

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform', self::DEFAULT_PLATFORM)
            ->addOption('input-dir', null, InputOption::VALUE_REQUIRED,  'The input directory', self::DEFAULT_INPUT_DIRECTORY)
            ->addOption('con', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'The name of the connection N.', array())
            ->addOption('dsn', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'The dsn for connection N.', array())
            ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (count($input->getOption('con')) !== count($input->getOption('dsn'))) {
            throw new InvalidArgumentException(sprintf(
                'The amount of connection "%d" does not match that of given dsn "%d".',
                count($input->getOption('con')), count($input->getOption('dsn'))
            ));
        }

        $dsnList = $input->getOption('dsn');
        foreach ($input->getOption('con') as $idx => $name) {
            $this->connections[$name] = $dsnList[$idx];
        }
    }

    protected function getBuildProperties($file)
    {
        $properties = array();

        if (false === $lines = @file($file)) {
            throw new RuntimeException(sprintf('Unable to parse contents of "%s".', $file));
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' == $line || in_array($line[0], array('#', ';'))) {
                continue;
            }

            $pos = strpos($line, '=');
            $properties[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
        }

        return $properties;
    }

    protected function getSchemas(InputInterface $input)
    {
        $finder = new Finder();

        return $finder
            ->name('*schema.xml')
            ->in($input->getOption('input-dir'))
            ->depth(0)
            ->files()
            ;
    }
}
