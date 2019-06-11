<?php

// Deps
use Bkwld\Cloner\ServiceProvider;
use Mockery as m;

class ServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function testProvides()
    {
        $app = m::mock('Illuminate\Foundation\Application');
        $provider = new ServiceProvider($app);
        $this->assertEquals([
            'cloner',
            'cloner.attachment-adapter',
        ], $provider->provides());
    }

    public function testRegister()
    {
        $app = m::mock('Illuminate\Foundation\Application')
            ->shouldReceive('singleton')->with('cloner', m::any())->once()
            ->shouldReceive('singleton')->with('cloner.attachment-adapter', m::any())->once()
            ->getMock();
        $provider = new ServiceProvider($app);
        $provider->register();
    }
}
