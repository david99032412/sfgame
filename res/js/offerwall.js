function Offerwall() {
    var _modal;
    var _modalBody;
    this.show = function (url) {
        if (typeof _modal !== "undefined") {
            _modal.style.display = "block";
            _modalBody.src = url;
            return;
        }

        // Modal
        var modal = document.createElement('div');
        modal.setAttribute('id', 'offerwallModal');
        modal.classList.add('offerwall-modal');

        // Modal content
        var modalContent = document.createElement('div');
        modalContent.classList.add('offerwall-modal-content');

        // Modal header
        var modalHeader = document.createElement('div');
        modalHeader.classList.add('offerwall-modal-header');

        var modalCloseButton = document.createElement('span');
        modalCloseButton.innerText = '\u00D7';
        modalCloseButton.classList.add('offerwall-close');
        modalCloseButton.addEventListener('click', function () {
            modal.style.display = "none";
        });
        modalHeader.appendChild(modalCloseButton);

        var modalTitle = document.createElement('h2');
        modalTitle.innerText = 'Offerwall';
        modalHeader.appendChild(modalTitle);
        modalContent.appendChild(modalHeader);

        // Model body
        _modalBody = document.createElement('iframe');
        _modalBody.style.border = '0';
        _modalBody.style.padding = '0';
        _modalBody.classList.add('offerwall-modal-body');
        _modalBody.src = url;

        modalContent.appendChild(_modalBody);

        modal.appendChild(modalContent);

        _modal = document.getElementsByTagName('body')[0].appendChild(modal);

        _modal.style.display = "block";
    }
}
