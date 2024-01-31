# Contributing to Freemius SDK for WordPress

We love to receive contributions from our community — you! There are many ways to contribute, from writing tutorials or blog posts, improving the documentation, submitting bug reports and feature requests or writing code which can be incorporated into Freemius SDK for WordPress itself.
Please be sure to read our [contributing guide](https://freemius.com/help/documentation/wordpress-sdk/freemius-sdk-contribute/) before making a pull request.

## Setup Node.js

We make use of several Node.js packages to build and test the SDK. To install them, you need to have Node.js installed on your machine. We recommend using
[nvm](https://github.com/nvm-sh/nvm). Once you have it installed, run the following commands to install the correct version of Node.js and the dependencies:

```bash
# Make sure to use the latest LTS version of Node.js.
nvm install --lts
nvm use --lts

# Install with a frozen lockfile.
npm ci
````

Now you may check the available commands by running:

```bash
npm run
```

## Translations

We use a custom build to extract translations and generate the POT file. The prerequisites are:

- You must be a team member of Freemius.
- You must have access to our [Transifex](https://app.transifex.com/freemius/wordpress-sdk/dashboard/) project.
- You have set the `.env` file in the project with the needed variables. Check the `.env.example` file for reference.

Now, you can run the following commands:


```bash
# Run the script to extract translations and generate the POT file.
npm run translate
```

## Development and Build

To compile SASS and JS during development, run

```bash
npm run dev
```

This will watch for changes and compile changed files. The system is also hooked with
live-reload, so you don't need to refresh the page to see the changes. Simply install the
[LiveReload browser extension](https://chromewebstore.google.com/detail/livereload++/ciehpookapcdlakedibajeccomagbfab) and enable it.

To build the SDK for production, run

```bash
npm run build
```