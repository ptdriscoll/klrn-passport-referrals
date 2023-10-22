import { parseData } from './data-parse.js';
import { errorMessages, getData, fadeInMain } from './data-helpers.js';

const startInput = document.querySelector('#start-date');
const endInput = document.querySelector('#end-date');
const submit = document.querySelector('#submit');

/**
 * On click, clears any error messages, sends date-range request to server,
 * and parses response.
 * @param {PointerEvent} e.
 */
function handleClick(e) {
  e.preventDefault();
  if (isValid()) {
    const req = { start: startInput.value, end: endInput.value };
    const errorElem = document.querySelector('#date-error');
    const graphicElems = [...document.querySelectorAll('.graphic.trends')];

    document.querySelector('main').classList.remove('fade-in');
    document.querySelector('#loader').classList.remove('hide');
    errorElem.classList.remove('fade-in');
    errorElem.innerHTML = '';
    graphicElems.forEach((g) => g.classList.add('hide'));

    getData(req)
      .then((res) => parseData(res))
      .then(() => fadeInMain())
      .catch((err) => console.log(err));
  }
}

/**
 * Checks to make sure start date is not after end date.
 * @param {string} start - Date in YYYY-MM-DD format.
 * @param {string} end - Date in YYYY-MM-DD format.
 * @return {boolean} - true = start is not after end date.
 */
function checkChronological(start, end) {
  const startDate = new Date(start);
  const endDate = new Date(end);
  return startDate <= endDate;
}

/**
 * Checks form validity, sets any error messages and returns validity boolean.
 * @return {boolean} - On whether form is valid.
 */
function isValid() {
  let noErrors = false;

  const datesAreChronological = checkChronological(
    startInput.value,
    endInput.value
  );

  if (startInput.validity.valueMissing) {
    startInput.setCustomValidity(errorMessages[0]);
    startInput.reportValidity();
  } else if (endInput.validity.valueMissing) {
    endInput.setCustomValidity(errorMessages[0]);
    endInput.reportValidity();
  } else if (startInput.validity.patternMismatch) {
    startInput.setCustomValidity(errorMessages[1]);
    startInput.reportValidity();
  } else if (endInput.validity.patternMismatch) {
    endInput.setCustomValidity(errorMessages[1]);
    endInput.reportValidity();
  } else if (!datesAreChronological) {
    endInput.setCustomValidity(errorMessages[2]);
    endInput.reportValidity();
  } else {
    startInput.setCustomValidity('');
    endInput.setCustomValidity('');
    noErrors = true;
  }

  return noErrors;
}

submit.addEventListener('click', handleClick);
