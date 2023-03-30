jQuery(document).ready(() => {
  const eventEmitter = window.prestashop.component.EventEmitter;

  eventEmitter.on('Module Upgraded', (context) => {
    const moduleElement = $(context);
    mboPostModuleUpgradeActions(moduleElement.data('tech-name'))
  });
});


function mboPostModuleUpgradeActions(moduleName) {
  window.$.ajax({
    method: 'POST',
    url: mboAfterModuleUpgradeRoute,
    dataType: 'json',
    data: {'moduleName': moduleName},
  }).done((response) => {
    console.log(response)
  });
}
