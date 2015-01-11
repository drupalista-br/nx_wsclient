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
 * Shell engine implementation using BSD find command.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
class BsdFindAdapter extends AbstractFindAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
        return 'bsd_find';
    }

    /**
     * {@inheritdoc}
     */
    protected function canBeUsed()
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
        return in_array($this->shell->getType(), array(Shell::TYPE_BSD, Shell::TYPE_DARWIN)) && parent::canBeUsed();
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
                $format = '%HT';
                break;
            case SortableIterator::SORT_BY_ACCESSED_TIME:
                $format = '%a';
                break;
            case SortableIterator::SORT_BY_CHANGED_TIME:
                $format = '%c';
                break;
            case SortableIterator::SORT_BY_MODIFIED_TIME:
                $format = '%m';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown sort options: %s.', $sort));
        }

        $command
            ->add('-print0 | xargs -0 stat -f')
            ->arg($format.'%t%N')
            ->add('| sort | cut -f 2');
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFindCommand(Command $command, $dir)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($command, $dir), false)) !== __AM_CONTINUE__) return $__am_res; 
        parent::buildFindCommand($command, $dir)->addAtIndex('-E', 1);

        return $command;
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
                ->add('| grep -v \'^$\'')
                ->add('| xargs -I{} grep -I')
                ->add($expr->isCaseSensitive() ? null : '-i')
                ->add($not ? '-L' : '-l')
                ->add('-Ee')->arg($expr->renderPattern())
                ->add('{}')
            ;
        }
    }
}
