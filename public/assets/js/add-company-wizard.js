'use strict';

$(function () {
  const select2 = $('.select2'),
    selectPicker = $('.selectpicker');

  // Bootstrap select
  if (selectPicker.length) {
    selectPicker.selectpicker();
  }

  // select2
  if (select2.length) {
    select2.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>');
      $this.select2({
        placeholder: 'Select value',
        dropdownParent: $this.parent()
      });
    });
  }
});

(function () {
  const wizardVertical = document.querySelector('#company-details-wizard');

  if (typeof wizardVertical !== undefined && wizardVertical !== null) {
    // Wizard form
    const wizardValidationForm = wizardVertical.querySelector('#company-details-form');
    // Wizard steps
    const wizardValidationFormStep1 = wizardValidationForm.querySelector('#company-details');
    const wizardValidationFormStep2 = wizardValidationForm.querySelector('#address-details');
    const wizardValidationFormStep3 = wizardValidationForm.querySelector('#relationship-manager-details');
    // const wizardValidationFormStep4 = wizardValidationForm.querySelector('#bank-details');

    const wizardVerticalBtnNext = [].slice.call(wizardVertical.querySelectorAll('.btn-next')),
      wizardVerticalBtnPrev = [].slice.call(wizardVertical.querySelectorAll('.btn-prev'));

    const verticalStepper = new Stepper(wizardVertical, {
      linear: false
    });

    // Company details
    const FormValidation1 = FormValidation.formValidation(wizardValidationFormStep1, {
      fields: {
        name: {
          validators: {
            notEmpty: {
              message: 'The name is required'
            },
            stringLength: {
              min: 2,
              max: 100,
              message: 'The name must be more than 2 and less than 100 characters long'
            }
          }
        },
        top_level_borrower_limit: {
          validators: {
            notEmpty: {
              message: 'The Top Level Borrower limit is required'
            }
          }
        },
        limit_expiry_date: {
          validators: {
            notEmpty: {
              message: 'The Limit Expiry Date is required'
            }
          }
        },
        cif: {
          validators: {
            notEmpty: {
              message: 'CIF is required'
            }
          }
        },
        unique_identification_number: {
          validators: {
            notEmpty: {
              message: 'The Unique Identification Number is required'
            }
          }
        },
        business_identification_number: {
          validators: {
            notEmpty: {
              message: 'The Business Identitification Number is required'
            }
          }
        },
        business_segment: {
          validators: {
            notEmpty: {
              message: 'Select the Business Segment'
            }
          }
        },
        kra_pin: {
          validators: {
            callback: {
              message: 'Invalid KRA PIN',
              callback: function (input) {
                let pass = true;
                const onlyLetters = /^[a-zA-Z]+$/;
                const first_is_letter = input.value.charAt(0);
                const last_is_letter = input.value.charAt(input.length - 1);
                // Check length
                if (input.value.length < 11 || input.value.length > 11) {
                  pass = false;
                } else {
                  // Check if first character is letter
                  if (onlyLetters.test(first_is_letter)) {
                    pass = true;
                    // Check if last character is letter
                    if (onlyLetters.test(last_is_letter)) {
                      pass = true;
                    } else {
                      pass = false;
                    }
                  } else {
                    pass = false;
                  }
                }
                return pass;
              }
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          // Use this for enabling/changing valid/invalid class
          eleInvalidClass: 'border-danger text-danger',
          eleValidClass: '',
          rowSelector: '.col-sm-6'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton()
      },
      init: instance => {
        instance.on('plugins.message.placed', function (e) {
          //* Move the error message out of the `input-group` element
          if (e.element.parentElement.classList.contains('input-group')) {
            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
          }
        });
      }
    }).on('core.form.valid', function () {
      // Jump to the next step when all fields in the current step are valid
      verticalStepper.next();
    });

    // Address details
    const FormValidation2 = FormValidation.formValidation(wizardValidationFormStep2, {
      fields: {
        city: {
          validators: {
            notEmpty: {
              message: 'Select City'
            }
          }
        },
        postal_code: {
          validators: {
            notEmpty: {
              message: 'Enter The Postal Code'
            }
          }
        },
        address: {
          validators: {
            notEmpty: {
              message: 'Enter the company address'
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          // Use this for enabling/changing valid/invalid class
          eleInvalidClass: 'border-danger text-danger',
          eleValidClass: '',
          rowSelector: '.col-sm-6'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton()
      },
      init: instance => {
        instance.on('plugins.message.placed', function (e) {
          //* Move the error message out of the `input-group` element
          if (e.element.parentElement.classList.contains('input-group')) {
            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
          }
        });
      }
    }).on('core.form.valid', function () {
      // Jump to the next step when all fields in the current step are valid
      verticalStepper.next();
    });

    // Relationship Manager details
    const FormValidation3 = FormValidation.formValidation(wizardValidationFormStep3, {
      fields: {
        relationship_manager_name: {
          validators: {
            notEmpty: {
              message: "Enter the relationship manager's name"
            }
          }
        },
        relationship_manager_email: {
          validators: {
            notEmpty: {
              message: "Enter the relationship manager's email"
            }
          }
        },
        relationship_manager_phone_number: {
          validators: {
            notEmpty: {
              message: "Enter the relationship manager's phone number"
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          // Use this for enabling/changing valid/invalid class
          eleInvalidClass: 'border-danger text-danger',
          eleValidClass: '',
          rowSelector: '.col-sm-6'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton()
      },
      init: instance => {
        instance.on('plugins.message.placed', function (e) {
          //* Move the error message out of the `input-group` element
          if (e.element.parentElement.classList.contains('input-group')) {
            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
          }
        });
      }
    }).on('core.form.valid', function () {
      // Jump to the next step when all fields in the current step are valid
      // verticalStepper.next();
      wizardValidationForm.submit();
    });

    // // Bank Details
    // const FormValidation4 = FormValidation.formValidation(wizardValidationFormStep4, {
    //   fields: {},
    //   plugins: {
    //     trigger: new FormValidation.plugins.Trigger(),
    //     bootstrap5: new FormValidation.plugins.Bootstrap5({
    //       // Use this for enabling/changing valid/invalid class
    //       eleInvalidClass: 'border-danger text-danger',
    //       eleValidClass: '',
    //       rowSelector: '.col-sm-6'
    //     }),
    //     autoFocus: new FormValidation.plugins.AutoFocus(),
    //     submitButton: new FormValidation.plugins.SubmitButton()
    //   },
    //   init: instance => {
    //     instance.on('plugins.message.placed', function (e) {
    //       //* Move the error message out of the `input-group` element
    //       if (e.element.parentElement.classList.contains('input-group')) {
    //         e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
    //       }
    //     });
    //   }
    // }).on('core.form.valid', function () {
    //   wizardValidationForm.submit();
    // });

    wizardVerticalBtnNext.forEach(item => {
      item.addEventListener('click', event => {
        // When click the Next button, we will validate the current step
        switch (verticalStepper._currentIndex) {
          case 0:
            FormValidation1.validate();
            break;

          case 1:
            FormValidation2.validate();
            break;

          case 2:
            FormValidation3.validate();
            break;

          // case 3:
          //   FormValidation3.validate();
          //   break;

          default:
            break;
        }
      });
    });

    wizardVerticalBtnPrev.forEach(item => {
      item.addEventListener('click', event => {
        switch (verticalStepper._currentIndex) {
          // case 3:
          //   verticalStepper.previous();
          //   break;

          case 2:
            verticalStepper.previous();
            break;

          case 1:
            verticalStepper.previous();
            break;

          case 0:

          default:
            break;
        }
      });
    });
  }
})();
