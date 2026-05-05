define([
    'jquery'
], function ($) {

    const divWidth = 328;  // Width of each div
    const divHeight = 426; // Height of each div
    const spacing = 20;
    const maxPerLayer = 8;
    const gap = 20;
    const layerOffsetXStep = 15;
    return {
        container: '',
        /**
         *
         * @param container
         */
        setContainer: function (container) {
            this.container = container;
        },
        /**
         *
         * @param index
         * @returns {{xPos: number, yPos: number, zindex: number}}
         */
        calculatePosition: function (index) {
            let atciveModelShow = 312;
            const isMobile = window.innerWidth <= 600;
            let defaultPerlayer = isMobile ? 1 : maxPerLayer;
            if (isMobile) {
                atciveModelShow = 0;
            }
            const screenWidth = window.innerWidth - atciveModelShow;
            const screenHeight = window.innerHeight;
            const baseX = screenWidth - divWidth - gap;
            const baseY = screenHeight - divHeight - gap;
            const layer = Math.floor(index / defaultPerlayer);
            const posInLayer = index % defaultPerlayer;
            const layerOffsetX = layer * layerOffsetXStep;
            const columnsPerRow = Math.max(1, Math.floor((screenWidth - 100) / (divWidth + gap)));
            const column = posInLayer % columnsPerRow;
            const row = Math.floor(posInLayer / columnsPerRow);
            const x = baseX - column * (divWidth + gap) - layerOffsetX;
            const y = baseY - row * (divHeight + gap);
            const position= {
                left: isMobile ? '80px' : x + 'px',
                top: isMobile ? '0px' : y + 'px',
                zIndex: 999999 + layer
            };

            return position;
        },
        /**
         * rePositionForAllChatBox
         * @param elems
         * @returns {{length}|*}
         */
        rePositionForAllChatBox: function (elems) {
            const self = this;
            if (elems.length) {
                elems.forEach((box, index) => {
                    const position = self.calculatePosition(index);
                    box.setDefaultChatBoxPosition(position);
                })
            }
            return elems;
        }
    }
})
