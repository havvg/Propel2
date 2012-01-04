<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Model\Database;
use Propel\Generator\Exception\BuildException;

/**
 * @author	William Durand <william.durand1@gmail.com>
 * @author  Toni Uebernickel <tuebernickel@gmail.com>
 */
class GraphvizManager extends AbstractManager
{
    public function build()
    {
        foreach ($this->getDataModels() as $dataModel) {
            foreach ($dataModel->getDatabases() as $database) {
                $this->log("db: " . $database->getName());
                $this->writeDot(self::createDotSyntax($database), $this->getWorkingDirectory(), $database->getName());
            }
        }
    }

    protected function writeDot($dotSyntax, $baseFilename)
    {
        $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $baseFilename . '.schema.dot';

        $this->log("Writing dot file to " . $file);

        file_put_contents($file, $dotSyntax);
    }

    /**
     * Create the DOT syntax for a given databases.
     *
     * @param Database $database
     *
     * @return string The DOT syntax created.
     */
    public static function createDotSyntax(Database $database)
    {
        $dotSyntax = '';

        // table nodes
        foreach ($database->getTables() as $table) {
            $columnsSyntax = '';
            foreach ($table->getColumns() as $column) {
                $attributes = '';

                if (count($column->getForeignKeys()) > 0) {
                    $attributes .= ' [FK]';
                }

                if ($column->isPrimaryKey()) {
                    $attributes .= ' [PK]';
                }

                $columnsSyntax .= sprintf('%s (%s)%s\l', $column->getName(), $column->getType(), $attributes);
            }

            $nodeSyntax = sprintf('node%s [label="{<table>%s|<cols>%s}", shape=record];', $table->getName(), $table->getName(), $columnsSyntax);
            $dotSyntax .= "$nodeSyntax\n";
        }

        // relation nodes
        foreach ($database->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                foreach ($column->getForeignKeys() as $fk) {
                    $relationSyntax = sprintf('node%s:cols -> node%s:table [label="%s=%s"];', $table->getName(), $fk->getForeignTableName(), $column->getName(), implode(',', $fk->getForeignColumns()));
                    $dotSyntax .= "$relationSyntax\n";
                }
            }
        }

        return sprintf("digraph G {\n%s}\n", $dotSyntax);
    }
}
