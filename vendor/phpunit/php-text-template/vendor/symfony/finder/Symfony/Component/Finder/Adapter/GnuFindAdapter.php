<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder\Adapter;

use Symfony\Component\Finder\Shell\Shell;
use Symfony\Component\Finder\Shell\Command;
use Symfony\Component\Finder\Iterator\SortableIterator;
use Symfony\Component\Finder\Expression\Expression;

/**
 * Shell engine implementation using GNU find command.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
class GnuFindAdapter extends AbstractFindAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
        return 'gnu_find';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFormatSorting(Command $command, $sort)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($command, $sort), false)) !== __AM_CONTINUE__) return $__am_res; 
        switch ($sort) {
            case SortableIterator::SORT_BY_NAME:
                $command->ins('sort')->add('| sort');

                return;
            case SortableIterator::SORT_BY_TYPE:
                $format = '%y';
                break;
            case SortableIterator::SORT_BY_ACCESSED_TIME:
                $format = '%A@';
                break;
            case SortableIterator::SORT_BY_CHANGED_TIME:
                $format = '%C@';
                break;
            case SortableIterator::SORT_BY_MODIFIED_TIME:
                $format = '%T@';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown sort options: %s.', $sort));
        }

        $command
            ->get('find')
            ->add('-printf')
            ->arg($format.' %h/%f\\n')
            ->add('| sort | cut')
            ->arg('-d ')
            ->arg('-f2-')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function canBeUsed()
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
        return $this->shell->getType() === Shell::TYPE_UNIX && parent::canBeUsed();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFindCommand(Command $command, $dir)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($command, $dir), false)) !== __AM_CONTINUE__) return $__am_res; 
        return parent::buildFindCommand($command, $dir)->add('-regextype posix-extended');
    }

    /**
     * {@inheritdoc}
     */
    protected function buildContentFiltering(Command $command, array $contains, $not = false)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($command, $contains, $not), false)) !== __AM_CONTINUE__) return $__am_res; 
        foreach ($contains as $contain) {
            $expr = Expression::create($contain);

            // todo: avoid forking process for each $pattern by using multiple -e options
            $command
                ->add('| xargs -I{} -r grep -I')
                ->add($expr->isCaseSensitive() ? null : '-i')
                ->add($not ? '-L' : '-l')
                ->add('-Ee')->arg($expr->renderPattern())
                ->add('{}')
            ;
        }
    }
}
