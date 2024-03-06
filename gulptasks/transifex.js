/**
 * All Transifex related APIs and code.
 *
 * @link https://developers.transifex.com/recipes/create-update-and-delete-a-resource-in-nodejs
 * @todo Refactor into a class/proper library if we end up using this at more places.
 */
const {transifexApi} = require('@transifex/api');
const fs = require('node:fs');
const log = require('fancy-log');

transifexApi.setup({
    auth: process.env.TRANSIFEX_API
});

const SOURCE_SLUG = 'freemius-enpo';
const SOURCE_NAME = 'freemius-en.po';

async function getOrganization() {
    // Safety check, unless we feel 100% confident, this wouldn't break the existing resources.
    if ('wordpress-sdk' === process.env.TRANSIFEX_ORGANIZATION) {
        throw new Error('Can not use the production organization yet!');
    }

    const organization = await transifexApi.Organization.get({slug: process.env.TRANSIFEX_ORGANIZATION});

    if (!organization) {
        throw new Error(`Organization "${process.env.TRANSIFEX_ORGANIZATION}" not found!`);
    }

    log(`Using organization "${organization.attributes.name}"`);

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

    log(`Using project "${project.attributes.name}"`);

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
        log(`Resource "${name}" already exists, updating...`)
    } catch (e) {
        // No resources yet
        log(`Creating resource "${name}"`);
        resource = await transifexApi.Resource.create({
            name,
            slug,
            project,
            i18n_format: i18nFormat,
        });
    }

    return resource;
}

async function uploadEnglishPoToTransifex(poPath) {
    const {organization, project} = await getOrgAndProject();
    const content = fs.readFileSync(poPath, {encoding: 'utf-8'});

    const i18nFormat = await transifexApi.i18n_formats.get({
        organization,
        name: 'PO'
    });

    const resource = await getResourceStringForUpload(project, SOURCE_NAME, SOURCE_SLUG, i18nFormat);

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