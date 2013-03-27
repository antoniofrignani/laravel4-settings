#Settings Package for Laravel 4

I borrowed ideas from several other config/preference/settings packages that exist for L4 that are
already out there, so this isn't really anything special and it is pretty specific to my needs.

## Current Features
- Store your config in the database
- Can still retrieve regular config items using the Settings::get() method as well
- Can overwrite config values using the ones that you've stored in the db.
- Other features. I hate doing readmes and it's mostly so I can remember.

##Using with L4
Start by adding the following line to your composer.php

```
"dberry37388/settings": "dev-master"
```

Open up your app/config/app.php file and add the following:

```
// under service providers add:
'Dberry37388\Settings\SettingsServiceProvider'

// under aliases add:
'Settings' => 'Dberry37388\Settings\Facades\SettingsFacade',
```

Now open up the command line and run:

```
php artisan migrate --package=dberry37388/settings
```

Now we're cooking with peanut oil... You should be all installed and ready to go.


## Using the Settings Package
The best way to see what is going on is to open up the code and take a gander, but here is a really
quick cheat sheet.

Getting, setting and forgetting values works the same way as the default Config in Laravel. So it
will look something like ```namespace::group.item```. Please read the Laravel config docs if you
need assistance with the naming of config values.

### Set a Value
You have a couple of choices here. Not that anything that you set here will override any Config values
that you have set manually using the config files.

#### Settings::set($key, $value = 'string or array')
Pretty self-explanatory. Will set a single value. Can either be a string or an array

```
// sets a var named helloworld
Settings::set("helloworld", 'Hello World! It's me');
```

#### Settings::setMultiple($keys = array())
Sometimes it's nice to just be able to set a bunch of values at the same time. This will look
something like this:

```
$keys = array(
	'page_title' => 'My Page Title',
	'users::section_title' => 'My Section Title'
);

// use the setMultiple method to add them all
Settings::setMultiple($keys);
```

#### Settings::setTemp($key, $value = 'string or array')
Sometimes you just need a temporary value set and don't want to actually add it to your DB. Anything
set here, is only valid for the current request.

```
// sets a var named helloworld
Settings::setTemp("helloworld", 'Hello World! It's me, for Now. Next time I could be different');
```

### Forgetting (Removing)

You guessed it, to forget or delete a setting just use:

```
Settings::forget('my.key.here');
```

This will remove the current setting from memory and also from the database.


### Get Settings
Okay, we now know how to set and forget values, but how about retrieving them?  It's pretty simple.

When a value is set, it is added to memory and is available by using Laravel's default method:

```
Config::get('helloworld')
```

It is also available by using:

```
Setting::get('helloworld');
```

It is up to you how you want to retrieve your values. If you want there to be a distinction between
the two, then use Settings::get().

## The Site Class
I've found that I use custom settings a lot for site information. Every site has a lot of common
needs, like page title, metadata, navigation menu helpers, etc... So I've implemented the **Site**
class to help with that.

The site class is basically just a wrapper around the Settings class, that comes pre-namespaced so
that we can keep all of the site's settings grouped together and also has the ability for you to
add custom macros, much like the HTML class.

### Examples

To set a page title

``` Site::set('page_title'); ```

Behind the scenes, this is actually setting site::page_title, but we've wrapped this so that you
don't have to worry about the namespacing since we will probably be using these all over.

### Macros
Macros are just little helpers that you can add yourself as needed. Here is an example macro that
is included for you:

```
/**
 * Gets our Page TItle
 * Will use the Site::get() to pull our page title from the config
 */
Site::macro('getPageTitle', function() {

	// return our page title from the site config
	return Site::get('page_title');
});
```

So now we can do ```Site::getPageTitle()``` to retrieve our page title.  Granted this is a very
trivial example, but you catch the drift here.

## That's All For Now :)
Hopefully you'll find this useful in some way, and please add any questions, comments, critiques, etc
that you might have.

This is still a WIP, but is pretty stable and usable right now.
