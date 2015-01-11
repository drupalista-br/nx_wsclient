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

/**
 * Interface for finder engine implementations.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
abstract class AbstractAdapter implements AdapterInterface
{
    protected $followLinks = false;
    protected $mode = 0;
    protected $minDepth = 0;
    protected $maxDepth = PHP_INT_MAX;
    protected $exclude = array();
    protected $names = array();
    protected $notNames = array();
    protected $contains = array();
    protected $notContains = array();
    protected $sizes = array();
    protected $dates = array();
    protected $filters = array();
    protected $sort = false;
    protected $paths = array();
    protected $notPaths = array();
    protected $ignoreUnreadableDirs = false;

    private static $areSupported = array();

    /**
     * {@inheritdoc}
     */
    public function isSupported()
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
        $name = $this->getName();

        if (!array_key_exists($name, self::$areSupported)) {
            self::$areSupported[$name] = $this->canBeUsed();
        }

        return self::$areSupported[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setFollowLinks($followLinks)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($followLinks), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->followLinks = $followLinks;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMode($mode)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($mode), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->mode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDepths(array $depths)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($depths), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->minDepth = 0;
        $this->maxDepth = PHP_INT_MAX;

        foreach ($depths as $comparator) {
            switch ($comparator->getOperator()) {
                case '>':
                    $this->minDepth = $comparator->getTarget() + 1;
                    break;
                case '>=':
                    $this->minDepth = $comparator->getTarget();
                    break;
                case '<':
                    $this->maxDepth = $comparator->getTarget() - 1;
                    break;
                case '<=':
                    $this->maxDepth = $comparator->getTarget();
                    break;
                default:
                    $this->minDepth = $this->maxDepth = $comparator->getTarget();
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setExclude(array $exclude)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($exclude), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setNames(array $names)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($names), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->names = $names;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setNotNames(array $notNames)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($notNames), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->notNames = $notNames;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setContains(array $contains)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($contains), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->contains = $contains;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setNotContains(array $notContains)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($notContains), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->notContains = $notContains;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSizes(array $sizes)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($sizes), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->sizes = $sizes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDates(array $dates)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($dates), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->dates = $dates;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilters(array $filters)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($filters), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->filters = $filters;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSort($sort)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($sort), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->sort = $sort;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPath(array $paths)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($paths), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->paths = $paths;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setNotPath(array $notPaths)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($notPaths), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->notPaths = $notPaths;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ignoreUnreadableDirs($ignore = true)
    { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($ignore), false)) !== __AM_CONTINUE__) return $__am_res; 
        $this->ignoreUnreadableDirs = (bool) $ignore;

        return $this;
    }

    /**
     * Returns whether the adapter is supported in the current environment.
     *
     * This method should be implemented in all adapters. Do not implement
     * isSupported in the adapters as the generic implementation provides a cache
     * layer.
     *
     * @see isSupported()
     *
     * @return bool Whether the adapter is supported
     */
    abstract protected function canBeUsed();
}
