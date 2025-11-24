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
  const wizardVertical = document.querySelector('#program-details-wizard');

  if (typeof wizardVertical !== undefined && wizardVertical !== null) {
    // Wizard form
    const wizardValidationForm = wizardVertical.querySelector('#program-details-form');
    // Wizard steps
    const wizardValidationFormStep1 = wizardValidationForm.querySelector('#program-details');
    const wizardValidationFormStep2 = wizardValidationForm.querySelector('#discount-details');
    const wizardValidationFormStep3 = wizardValidationForm.querySelector('#comm-details');
    const wizardValidationFormStep4 = wizardValidationForm.querySelector('#bank-details');

    const wizardVerticalBtnNext = [].slice.call(wizardVertical.querySelectorAll('.btn-next')),
      wizardVerticalBtnPrev = [].slice.call(wizardVertical.querySelectorAll('.btn-prev'));

    const verticalStepper = new Stepper(wizardVertical, {
      linear: false
    });

    // program details
    const FormValidation1 = FormValidation.formValidation(wizardValidationFormStep1, {
      fields: {
        program_type_id: {
          validators: {
            notEmpty: {
              message: 'Select program type'
            }
          }
        },
        name: {
          validators: {
            notEmpty: {
              message: 'The name is required'
            },
            stringLength: {
              min: 3,
              max: 30,
              message: 'The name must be more than 3 and less than 30 characters long'
            }
            // regexp: {
            //   regexp: /^[a-zA-Z0-9 ]+$/,
            //   message: 'The name can only consist of alphabetical, number and space'
            // }
          }
        },
        anchor_id: {
          validators: {
            notEmpty: {
              message: 'Select Anchor'
            }
          }
        },
        eligibility: {
          validators: {
            notEmpty: {
              message: 'Enter Invoice Eligibility'
            },
            lessThan: {
              inclusive: true,
              max: 100,
              message: 'Maximum Eligibility is 100%'
            },
            greaterThan: {
              inclusive: true,
              min: 1,
              message: 'Minimum Eligibility is 1%'
            }
          }
        },
        approved_date: {
          validators: {
            notEmpty: {
              message: 'Enter Approval Date'
            }
          }
        },
        request_auto_finance: {
          validators: {
            notEmpty: {
              message: 'Select if requests are auto-financed'
            }
          }
        },
        program_limit: {
          validators: {
            notEmpty: {
              message: 'Enter Program Limit'
            },
            greaterThan: {
              inclusive: true,
              min: 0,
              message: 'Enter Program Limit'
            }
          }
        },
        // recourse: {
        //   validators: {
        //     notEmpty: {
        //       message: 'Select Recourse'
        //     }
        //   }
        // },
        max_limit_per_account: {
          validators: {
            notEmpty: {
              message: 'Enter Max Limit Per Account'
            },
            callback: {
              message: 'Limit per account cannot be more than program limit',
              callback: function (input) {
                let program_limit = document.getElementById('program-limit').value;
                if (Number(input.value.replaceAll(',', '')) > Number(program_limit.replaceAll(',', ''))) {
                  return false;
                }
                return true;
              }
            }
          }
        },
        repayment_appropriation: {
          validators: {
            callback: {
              message: 'Select Repayment Appropriation',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Vendor Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        due_date_calculated_from: {
          validators: {
            callback: {
              message: 'Select when due date is calculated from',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Dealer Financing') {
                  // If program type is 'Dealer Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Dealer Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        min_financing_days: {
          validators: {
            message: 'Enter the minimum financing days',
            callback: function (input) {
              let program_type_text = $('#product-type').find(':selected').text();
              if (program_type_text === 'Vendor Financing') {
                // If program type is 'Dealer Financing'
                if (input.value === '' || input.value === null) {
                  return false; // Allow empty for Dealer Financing
                } else {
                  return true; // Disallow any other value
                }
              }
            }
          }
        },
        limit_expiry_date: {
          validators: {
            notEmpty: {
              message: 'Enter the expiry date'
            }
          }
        },
        default_payment_terms: {
          validators: {
            notEmpty: {
              message: 'Enter the default payment terms'
            }
          }
        },
        days_limit_for_due_date_change: {
          validators: {
            notEmpty: {
              message: 'Enter number of days when due is allowed to be changed'
            }
          }
        },
        max_days_due_date_extension: {
          validators: {
            notEmpty: {
              message: 'Enter number of days when due can be changed to'
            }
          }
        },
        mandatory_invoice_attachment: {
          validators: {
            notEmpty: {
              message: 'Select if Invoice attachment is required'
            }
          }
        },
        account_status: {
          validators: {
            notEmpty: {
              message: 'Select whether active or suspended'
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
        declarative: new FormValidation.plugins.Declarative({
          html5Input: true
        }),
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

    // discount details
    const FormValidation2 = FormValidation.formValidation(wizardValidationFormStep2, {
      fields: {
        // benchmark_title: {
        //   validators: {
        //     callback: {
        //       message: 'Select the benchmark rate',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Vendor Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        // dealer_benchmark_title: {
        //   validators: {
        //     callback: {
        //       message: 'Select the benchmark rate',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Dealer Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        // business_strategy_spread: {
        //   validators: {
        //     callback: {
        //       message: 'Enter the business strategy spread',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Vendor Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        // credit_spread: {
        //   validators: {
        //     callback: {
        //       message: 'Enter the credit spread',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Vendor Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        // anchor_discount_bearing: {
        //   validators: {
        //     callback: {
        //       message: 'Enter the anchor/buyer bearing discount',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Vendor Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        discount_type: {
          validators: {
            callback: {
              message: 'Select the discount type',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Vendor Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        // fee_type: {
        //   validators: {
        //     callback: {
        //       message: 'Select the fee type',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Vendor Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        dealer_discount_type: {
          validators: {
            callback: {
              message: 'Select the discount type',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Dealer Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        // dealer_fee_type: {
        //   validators: {
        //     callback: {
        //       message: 'Select the fee type',
        //       callback: function (input) {
        //         let program_type_text = $('#product-type').find(':selected').text();
        //         if (program_type_text === 'Dealer Financing') {
        //           // If program type is 'Vendor Financing'
        //           if (input.value === '' || input.value === null) {
        //             return false; // Allow empty for Vendor Financing
        //           } else {
        //             return true; // Disallow any other value
        //           }
        //         }
        //       }
        //     }
        //   }
        // },
        'from_day[]': {
          validators: {
            callback: {
              message: 'Enter the discount from day',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Dealer Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        'to_day[]': {
          validators: {
            callback: {
              message: 'Enter the discount to day',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Dealer Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        'dealer_credit_spread[]': {
          validators: {
            callback: {
              message: 'Enter the credit spread',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Dealer Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
              }
            }
          }
        },
        'dealer_business_strategy_spread[]': {
          validators: {
            callback: {
              message: 'Enter the business strategy spread',
              callback: function (input) {
                let program_type_text = $('#product-type').find(':selected').text();
                if (program_type_text === 'Dealer Financing') {
                  // If program type is 'Vendor Financing'
                  if (input.value === '' || input.value === null) {
                    return false; // Allow empty for Vendor Financing
                  } else {
                    return true; // Disallow any other value
                  }
                }
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
          rowSelector: function (field, ele) {
            // field is the field name
            // ele is the field element
            switch (field) {
              case 'dealer_credit_spread[]':
                return '.col-sm-3';
              case 'dealer_business_strategy_spread[]':
                return '.col-sm-3';

              default:
                return '.col-sm-6';
            }
          }
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
      fields: {},
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
      verticalStepper.next();
    });

    // Anchor Bank details
    const FormValidation4 = FormValidation.formValidation(wizardValidationFormStep4, {
      fields: {
        'bank_names_as_per_bank[]': {
          validators: {
            notEmpty: {
              message: "Enter the anchor's account name as per bank"
            }
          }
        },
        'account_numbers[]': {
          validators: {
            notEmpty: {
              message: "Enter the anchor's bank account number"
            }
          }
        },
        'bank_names[]': {
          validators: {
            notEmpty: {
              message: 'Select bank'
            }
          }
        },
        'swift_codes[]': {
          validators: {
            notEmpty: {
              message: 'Enter the switft code'
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
      wizardValidationForm.submit();
    });

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

          case 3:
            FormValidation4.validate();

          default:
            break;
        }
      });
    });

    wizardVerticalBtnPrev.forEach(item => {
      item.addEventListener('click', event => {
        switch (verticalStepper._currentIndex) {
          case 3:
            verticalStepper.previous();
            break;

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
