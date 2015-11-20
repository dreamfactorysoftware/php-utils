<?php namespace DreamFactory\Library\Utility\Enums;

/**
 * The locations where things can be deployed
 */
class DeploymentLocations extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @type boolean We're not in Kansas anymore!
     */
    const OTHER = false;
    /**
     * @type int Stand-alone/locally hosted
     */
    const LOCAL = 0;
    /**
     * @type int DreamFactory Enterprise
     */
    const DFE = 1;
    /**
     * @type int IBM Bluemix
     */
    const BLUEMIX = 2;
    /**
     * @type int Cloud Foundry
     */
    const CLOUD_FOUNDRY = 3;
    /**
     * @type int Pivotal
     */
    const PIVOTAL = 4;
    /**
     * @type int Heroku
     */
    const HEROKU = 5;
    /**
     * @type int OpenShift
     */
    const OPEN_SHIFT = 6;
    /**
     * @type int OpenStack
     */
    const OPEN_STACK = 7;
    /**
     * @type int Docker
     */
    const DOCKER = 8;
    /**
     * @type int Amazon EC2
     */
    const AMAZON_EC2 = 9;
}
