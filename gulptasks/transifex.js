/**
 * All Transifex related APIs and code.
 *
 * @link https://developers.transifex.com/recipes/create-update-and-delete-a-resource-in-nodejs
 * @todo Refactor into a class/proper library if we end up using this at more places.
 */
const {transifexApi} = require('@transifex/api');
const fs = require('node:fs');
const log = require('fancy-log');
const prompts = require('prompts');
const {po} = require('gettext-parser');
const chalk = require('chalk');
const fetch = require('node-fetch'); // @todo - Remove once fetch is stabilized in NodeJS.

transifexApi.setup({
    auth: process.env.TRANSIFEX_API
});

const SOURCE_SLUG = 'freemius-enpo';
const SOURCE_NAME = 'freemius-en.po';

async function getOrganization() {
    const organization = await transifexApi.Organization.get({slug: process.env.TRANSIFEX_ORGANIZATION});

    if (!organization) {
        throw new Error(`Organization "${process.env.TRANSIFEX_ORGANIZATION}" not found!`);
    }

    log(`Using organization "${chalk.bold.bgBlack.yellow(organization.attributes.name)}"`);

    return organization;
}

/**
 * @param {import('@transifex/api').JsonApiResource} organization
 * @return {Promise<import('@transifex/api').JsonApiResource>}
 */
async function getProject(organization) {
    const projects = await organization.fetch('projects', false);
    const project = await projects.get({slug: process.env.TRANSIFEX_PROJECT});

    if (!project) {
        throw new Error(`Project "${process.env.TRANSIFEX_PROJECT}" not found!`);
    }

    log(`Using project "${chalk.bold.bgBlack.yellow(project.attributes.name)}"`);

    return project;
}

async function getOrgAndProject() {
    const organization = await getOrganization();
    const project = await getProject(organization);

    return {organization, project};
}

/**
 * @param {import('@transifex/api').JsonApiResource} project
 * @param {string} name
 * @param {string} slug
 * @param {import('@transifex/api').JsonApiResource} i18nFormat
 * @return {Promise<import('@transifex/api').JsonApiResource>}
 */
async function getResourceStringForUpload(project, name, slug, i18nFormat) {
    const resources = await project.fetch('resources', false);
    /**
     * IMPORTANT: DO NOT DELETE THE RESOURCE from the API.
     * It will delete all the translations too.
     * So first try to see if the resource is present and use it. If not, then only create it.
     */

    /**
     * @type {import('@transifex/api').JsonApiResource}
     */
    let resource;

    try {
        resource = await resources.get({slug});
        log(`Resource "${chalk.bold.bgBlack.yellow(name)}" already exists, updating...`)
    } catch (e) {
        // No resources yet
        log(`Creating resource "${chalk.bold.bgBlack.yellow(name)}"`);
        resource = await transifexApi.Resource.create({
            name,
            slug,
            project,
            i18n_format: i18nFormat,
        });
    }

    return resource;
}

function getLinesCount(parsedPot) {
    let sentenceCount = 0;
    let wordCount = 0;

    Object.values(parsedPot.translations).forEach((messages) => {
        Object.keys((messages)).forEach((source) => {
            if ('' === source) {
                return;
            }

            sentenceCount += 1;
            wordCount += source.split(' ').length;
        });
    });

    return {sentenceCount, wordCount};
}

async function uploadEnglishPoToTransifex(poPath) {
    const {organization, project} = await getOrgAndProject();
    const content = fs.readFileSync(poPath, {encoding: 'utf-8'});

    // Get total number of lines in the file
    const parsedPot = po.parse(content);

    // Get total lines count from the parsedPot
    const {sentenceCount, wordCount} = getLinesCount(parsedPot);

    const i18nFormat = await transifexApi.i18n_formats.get({
        organization,
        name: 'PO'
    });

    const resource = await getResourceStringForUpload(project, SOURCE_NAME, SOURCE_SLUG, i18nFormat);

    log(`ATTENTION: ${chalk.bold.red('UPLOADING')}!!`);
    log(`You are about to upload the freemius-en.po file to the production organization!.`);
    log(`In ${chalk.bgYellow.black('Transifex')} freemius-en.po file has ${chalk.bold.magenta(resource.attributes.string_count)} lines and ${chalk.bold.cyan(resource.attributes.word_count)} words.`);
    log(`In     ${chalk.bgYellow.black('Local')} freemius-en.po file has ${chalk.bold.magenta(sentenceCount)} lines and ${chalk.bold.cyan(wordCount)} words.`);
    log(`Please make sure you have already tested the content of the file.`);
    log(chalk.bold.red('Any data loss could be permanent.'));

    const confirmation = await prompts({
        type: 'confirm',
        message: `Do you want to upload the file freemius-en.po to Transifex?`,
        initial: false,
        name: 'value',
    });

    if (!confirmation.value) {
        log('Aborting upload');
        return false;
    }

    await transifexApi.ResourceStringsAsyncUpload.upload({
        resource,
        content,
    });

    return resource;
}

/**
 * @param {string} languageCode
 * @param {import('@transifex/api').JsonApiResource} resource
 * @return {Promise<string>}
 */
async function getTranslation(languageCode, resource) {
    const language = await transifexApi.Language.get({code: languageCode});
    const url = await transifexApi.ResourceTranslationsAsyncDownload.download({
        resource,
        language,
    });

    const response = await fetch(url);
    return await response.text();
}

exports.uploadEnglishPoToTransifex = uploadEnglishPoToTransifex;
exports.getTranslation = getTranslation;