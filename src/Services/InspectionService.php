<?php namespace DreamFactory\Library\Utility\Services;

class InspectionService
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array Installed packages
     */
    protected $installed;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Gets the list of installed packages
     *
     * @param string|null $filter  Optional 'grep' filter for packages
     * @param bool        $refresh If true, and a package list exists, it is refreshed
     *
     * @return array
     */
    public function getInstalledPackages( $filter = null, $refresh = false )
    {
        if ( !empty( $installed ) && !$refresh )
        {
            return $this->installed;
        }

        $_list = null;
        $filter && $filter = '|grep "' . $filter . '"';

        switch ( $this->getOperatingSystem() )
        {
            case 'redhat':
                $_list = trim( `rpm -qa --queryformat "%{NAME}\n" {$filter}` );
                break;

            case 'debian':
                $_list = trim( `dpkg --get-selections|grep -v 'deinstall' {$filter}` );
                break;

            default:
                throw new \RuntimeException( 'This operating system is not supported.' );
        }

        $_packages = [];

        if ( !empty( $_list ) )
        {
            foreach ( explode( PHP_EOL, $_list ) as $_package )
            {
                if ( false !== strpos( trim( $_package ), 'install' ) )
                {
                    $_package = trim( str_replace( 'install', null, $_package ) );
                }

                $_packages[] = $_package;
            }
        }

        return $this->installed = $_packages;
    }

    /**
     * @param string $name The package name
     *
     * @return bool True if it is installed
     */
    public function hasPackage( $name )
    {
        $_packages = $this->getInstalledPackages();

        foreach ( $_packages as $_package )
        {
            if ( false !== stripos( $_package, $name ) )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the OS type as a short string (i.e. 'linux', 'debian', 'redhat', 'darwin'
     *
     * @return string
     */
    protected function getOperatingSystem()
    {
        $_uname = strtolower( php_uname( 's' ) );

        if ( 'linux' != $_uname && 'darwin' != $_uname )
        {
            return $_uname;
        }

        if ( `which apt-get` )
        {
            return 'debian';
        }

        if ( `which yum` )
        {
            return 'redhat';
        }

        return $_uname;
    }
}
