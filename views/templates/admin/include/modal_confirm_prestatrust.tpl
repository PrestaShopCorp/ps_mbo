<div id="modal-prestatrust" class="modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{l s='Module verification' d='Admin.Modules.Feature'}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-md-2 text-sm-center">
              <img id="pstrust-img" src="" alt=""/>
            </div>
            <div class="col-md-10">
              <dl class="row">
                <dt class="col-sm-3">{l s='Module' d='Admin.Global'}</dt>
                <dd class="col-sm-9">
                    <strong id="pstrust-name"></strong>
                </dd>
                <dt class="col-sm-3">{l s='Author' d='Admin.Modules.Feature'}</dt>
                <dd class="col-sm-9" id="pstrust-author"></dd>
                <dt class="col-sm-3">{l s='Status' d='Admin.Global'}</dt>
                <dd class="col-sm-9"><strong><span class="text-info" id="pstrust-label"></span></strong></dd>
              </dl>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info" id="pstrust-message" role="alert">
                    <p class="alert-text"></p>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <div id="pstrust-btn-property-ok">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{l s='Back to modules list' d='Admin.Modules.Feature'}</button>
            <button type="submit" class="btn btn-primary pstrust-install">{l s='Proceed with the installation' d='Admin.Modules.Feature'}</button>
        </div>
        <div id="pstrust-btn-property-nok">
            <button type="submit" class="btn btn-outline-secondary pstrust-install">{l s='Proceed with the installation' d='Admin.Modules.Feature'}</button>
            <a href="" class="btn btn-primary" id="pstrust-buy" target="_blank">{l s='Buy module' d='Admin.Modules.Feature'}</a>
        </div>
      </div>
    </div>
  </div>
</div>
