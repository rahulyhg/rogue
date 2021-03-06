<?php

namespace Tests;

use Rogue\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\HomePage;
use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Get the default user for Dusk's login() helper.
     *
     * @return User
     */
    protected function user()
    {
        return factory(User::class, 'admin')->create();
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--no-sandbox', // Allows ChromeDriver to run on Wercker.
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515', DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     *  Logs user into Rogue.
     */
    public function login(Browser $browser)
    {
        $browser->visit(new HomePage)
                ->click('@login-button')
                ->assertPathIs('/register')
                ->clickLink('Log In')
                ->type('username', env('NORTHSTAR_EMAIL'))
                ->type('password', env('NORTHSTAR_PASSWORD'))
                ->press('Log In');
    }
}
