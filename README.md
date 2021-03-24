# Spoke & Chain

Spoke & Chain is the demo site for Craft Commerce.

## Local development

### Dependencies

The first step is to install the dependencies for the project. Make sure you are in the project folder.

```
composer install
npm install
```

### Environment

Make sure to duplicate the `.env.example` file and fill out all the details.

```
cp .env.example .env
```

#### Notable environment variables

`DEVSERVER_PUBLIC`, `DEVSERVER_PORT` and `DEVSERVER_HOST` are environment variables that are used when locally developing the CSS and JS within the project.

They are used in the `webpack-dev-server`. The key to this when using [nitro](https://github.com/craftcms/nitro) is that it needs to be accessible from within the VM.

This is because the project using twigpack and needs to be able to communicate with webpack dev server to enable hot module replacement.

Using [nitro](https://github.com/craftcms/nitro) version 2 the loopback IP address is made easier with `host.docker.internal`.

Your env file will end up looking something like the following:

```
# webpack dev server
DEVSERVER_PUBLIC=http://127.0.0.1:8080
DEVSERVER_PORT=8080
DEVSERVER_HOST=127.0.0.1

# Twigpack
TWIGPACK_MANIFEST_PATH=http://host.docker.internal:8080/
TWIGPACK_PUBLIC_PATH=http://host.docker.internal:8080/
```

The port can be any port number of your choosing.

### Developing

When developing on the project you can simply run the following command

```
npm run serve
```

This will start the `webpack-dev-server` and show a webpack dashboard in your terminal. You are then free to develop as required with the benefit of hot module replacement.

#### Purge CSS

Purge CSS is running in this project. This is due to using tailwindcss and not wanting to have a bloated CSS file of many redundant/unused CSS.

When actively developing and using the `serve` npm script, purge is not active. This means you will have access to all the of CSS. It also means that you need to make sure you are checking the site after building the files.

It is best to always try to use the full class names when developing this makes it easy for purge to do its thing.

Here is an example of running a loop.

```twig
{# Bad Example #}
{% set classes = ['100', '500', '900'] %}
{% for class in classes %}
  <div class="text-red-{{ class }}"></div>	
{% endfor %}

{# Good example #}
{% set classes = ['text-red-100', 'text-red-500', 'text-red-900'] %}
{% for class in classes %}
  <div class="{{ class }}"></div>	
{% endfor %}
```

Purge will scan the templates and the JS files looking for class names to whitelist to be allowed in the final build files.

If there is no way to avoid programmatic concatenation of class names you can manually add the classes to the `whitelist` array in the `tailwind.config.js` file in the root of the project.

This is parsed by purge and will make sure they are included in the build files. This is also handy if there are any libraries that are being used and need to have their class names included.

### Building

Once you have finished developing, the last thing to do is to build the files this is done with the following command.

```
npm run build
```

This will build all the files into the `web/dist` folder ready to be committed.

### Testing

Cypress tests cover multiple parts of the website:
- **CP** – Make sure that the content structure is defined properly.
- **Front-end** – Check that the different sections of the website work as expected.
- **Accessibility** – Check that the website complies with WCAG 2.0 guidelines.

First you need to define environment variables for Cypress. Copy the `cypress.example.json` file to `cypress.json` and adjust it to your needs:
```
cp cypress.example.json cypress.json
```

Open the Cypress Test Runner from the project root:
```
npx cypress open
```

Open accessibility tests only:
```
npx cypress open --config testFiles=./front/a11y/*.spec.js
```