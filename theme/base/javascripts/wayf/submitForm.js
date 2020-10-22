import {addSelectedIdp} from './deleteDisable/addSelectedIdp';
import {rememberChoice} from './rememberChoice';
import {getData} from '../utility/getData';
import {handleClickingDisabledIdp} from './handleClickingDisabledIdp';
import {hasVisibleDisabledButtonAsTarget} from './utility/hasVisibleDisabledButtonAsTarget';
import {hasVisibleDeleteButtonAsTarget} from './utility/hasVisibleDeleteButtonAsTarget';

/**
 * Submit the form for the selected idp.
 * This ensures you select the idp to log in.
 *
 * Also checks for the remember choice feature and handles it accordingly.
 *
 * @param e
 * @param previouslySelectedIdps
 */
export const submitForm = (e, previouslySelectedIdps) => {
  e.preventDefault();
  let element = e.target;

  if (hasVisibleDeleteButtonAsTarget(element)) {
    return;
  }

  if (element.tagName !== 'ARTICLE') {
    element = element.closest('.wayf__idp');
  }
  selectAndSubmit(element, previouslySelectedIdps);
};

/**
 * Performs the actual remember and sumbit logic. Where the submitForm
 * method both verifies and submits the form. This method only does
 * the latter.
 *
 * @param element
 * @param previouslySelectedIdps
 */
export const selectAndSubmit = (element, previouslySelectedIdps) => {
  if (hasVisibleDisabledButtonAsTarget(element)) {
    handleClickingDisabledIdp(element);
    return;
  }

  rememberChoice(getData(element, 'entityid'));

  addSelectedIdp(previouslySelectedIdps, element);
  element.querySelector('.idp__form').submit();
};
