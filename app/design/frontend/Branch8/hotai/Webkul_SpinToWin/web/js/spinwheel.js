window.Spinwheel = function (a, c) {
    defaultOptions = {
        canvasId: "canvas",
        centerX: null,
        centerY: null,
        outerRadius: null,
        innerRadius: 0,
        numSegments: 1,
        drawMode: "code",
        rotationAngle: 0,
        textFontFamily: "'Noto Sans TC', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        textFontSize: 20,
        textFontWeight: "bold",
        textOrientation: "horizontal",
        textAlignment: "center",
        textDirection: "normal",
        textMargin: null,
        textFillStyle: "black",
        textStrokeStyle: null,
        textLineWidth: 1,
        fillStyle: "silver",
        strokeStyle: "black",
        lineWidth: 1,
        clearTheCanvas: !0,
        imageOverlay: !1,
        drawText: !0,
        pointerAngle: 0,
        wheelImage: null,
        imageDirection: "N",
        responsive: !1,
        scaleFactor: 1,
        parts: null
    };
    for (var b in defaultOptions) this[b] = null != a && "undefined" !== typeof a[b] ? a[b] : defaultOptions[b];
    if (null != a) for (var d in a) "undefined" === typeof this[d] && (this[d] = a[d]);
    this.canvasId
        ? (this.canvas = document.getElementById(this.canvasId))
            ? (null == this.centerX && (this.centerX = this.canvas.width / 2),
              null == this.centerY && (this.centerY = this.canvas.height / 2),
              null == this.outerRadius && (this.outerRadius = this.canvas.width < this.canvas.height ? this.canvas.width / 2 - this.lineWidth : this.canvas.height / 2 - this.lineWidth),
              (this.ctx = this.canvas.getContext("2d")))
            : (this.ctx = this.canvas = null)
        : (this.ctx = this.canvas = null);
    this.segments = Array(null);
    for (b = 1; b <= this.numSegments; b++) this.segments[b] = null != a && a.segments && "undefined" !== typeof a.segments[b - 1] ? new Segment(a.segments[b - 1]) : new Segment();
    this.updateSegmentSizes();
    null === this.textMargin && (this.textMargin = this.textFontSize / 1.7);
    this.animation = null != a && a.animation && "undefined" !== typeof a.animation ? new Animation(a.animation) : new Animation();
    null != a && a.pins && "undefined" !== typeof a.pins && (this.pins = new Pin(a.pins));
    "image" == this.drawMode || "segmentImage" == this.drawMode
        ? ("undefined" === typeof a.fillStyle && (this.fillStyle = null),
          "undefined" === typeof a.strokeStyle && (this.strokeStyle = "red"),
          "undefined" === typeof a.drawText && (this.drawText = !1),
          "undefined" === typeof a.lineWidth && (this.lineWidth = 1),
          "undefined" === typeof c && (c = !1))
        : "undefined" === typeof c && (c = !0);
    this.pointerGuide = null != a && a.pointerGuide && "undefined" !== typeof a.pointerGuide ? new PointerGuide(a.pointerGuide) : new PointerGuide();
    this.responsive &&
        ((spinwheelToDrawDuringAnimation = this),
        (this._originalCanvasWidth = this.canvas.width),
        (this._originalCanvasHeight = this.canvas.height),
        (this._responsiveScaleHeight = this.canvas.dataset.responsivescaleheight),
        (this._responsiveMinWidth = this.canvas.dataset.responsiveminwidth),
        (this._responsiveMinHeight = this.canvas.dataset.responsiveminheight),
        (this._responsiveMargin = this.canvas.dataset.responsivemargin),
        window.addEventListener("load", spinwheelResize),
        window.addEventListener("resize", spinwheelResize));
    if (1 == c) this.draw(this.clearTheCanvas);
    else if ("segmentImage" == this.drawMode)
        for (spinwheelToDrawDuringAnimation = this, spinhweelAlreadyDrawn = !1, b = 1; b <= this.numSegments; b++)
            null !== this.segments[b].image && ((this.segments[b].imgData = new Image()), (this.segments[b].imgData.onload = spinwheelLoadedImage), (this.segments[b].imgData.src = this.segments[b].image));
};
Spinwheel.prototype.updateSegmentSizes = function () {
    if (this.segments) {
        for (var a = 0, c = 0, b = 1; b <= this.numSegments; b++) null !== this.segments[b].size && ((a += this.segments[b].size), c++);
        b = 360 - a;
        a = 0;
        0 < b && (a = b / (this.numSegments - c));
        c = -a/2; // 0;
        for (b = 1; b <= this.numSegments; b++) (this.segments[b].startAngle = c), (c = this.segments[b].size ? c + this.segments[b].size : c + a), (this.segments[b].endAngle = c);
    }
};
Spinwheel.prototype.clearCanvas = function () {
    // console.log("Clearing canvas");
    this.ctx && this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
};
Spinwheel.prototype.draw = function (a) {
    // console.log("Drawing spinwheel", a, this.ctx, this);
    this.ctx &&
        ("undefined" !== typeof a ? 1 == a && this.clearCanvas() : this.clearCanvas(),
        "image" == this.drawMode
            ? (this.drawWheelImage(), 1 == this.drawText && this.drawSegmentText(), 1 == this.imageOverlay && this.drawSegments())
            : "segmentImage" == this.drawMode
            ? (this.drawSegmentImages(), 1 == this.drawText && this.drawSegmentText(), 1 == this.imageOverlay && this.drawSegments())
            : (this.drawSegments(), 1 == this.drawText && this.drawSegmentText()),
        "undefined" !== typeof this.pins && 1 == this.pins.visible && this.drawPins(),
        1 == this.pointerGuide.display && this.drawPointerGuide());
};
Spinwheel.prototype.drawPins = function () {
    // console.log("Drawing pins");
    if (this.pins && this.pins.number) {
        var a = this.centerX * this.scaleFactor,
            c = this.centerY * this.scaleFactor,
            b = this.outerRadius * this.scaleFactor,
            d = this.pins.outerRadius,
            e = this.pins.margin;
        this.pins.responsive && ((d = this.pins.outerRadius * this.scaleFactor), (e = this.pins.margin * this.scaleFactor));
        for (var t = 360 / this.pins.number, h = 1; h <= this.pins.number; h++)
            this.ctx.save(),
                (this.ctx.strokeStyle = this.pins.strokeStyle),
                (this.ctx.lineWidth = 0),
                (this.ctx.fillStyle = this.pins.fillStyle),
                this.ctx.translate(a, c),
                this.ctx.rotate(this.degToRad(h * t + this.rotationAngle)),
                this.ctx.translate(-a, -c),
                this.ctx.beginPath(),
                this.ctx.arc(a, c - b + d + e, d, 0, 2 * Math.PI),
                this.pins.fillStyle && this.ctx.fill(),
                this.pins.strokeStyle && this.ctx.stroke(),
                this.ctx.restore();
    }
};
Spinwheel.prototype.drawPointerGuide = function () {
    // console.log("Drawing pointer guide");
    if (this.ctx) {
        var a = this.centerX * this.scaleFactor,
            c = this.centerY * this.scaleFactor,
            b = this.outerRadius * this.scaleFactor;
        this.ctx.save();
        this.ctx.translate(a, c);
        this.ctx.rotate(this.degToRad(this.pointerAngle));
        this.ctx.translate(-a, -c);
        this.ctx.strokeStyle = this.pointerGuide.strokeStyle;
        this.ctx.lineWidth = this.pointerGuide.lineWidth;
        this.ctx.beginPath();
        this.ctx.moveTo(a, c);
        this.ctx.lineTo(a, -(b / 4));
        this.ctx.stroke();
        this.ctx.restore();
    }
};
Spinwheel.prototype.drawWheelImage = function () {
    // console.log("Drawing wheel image");
    if (null != this.wheelImage) {
        var a = this.centerX * this.scaleFactor,
            c = this.centerY * this.scaleFactor,
            b = this.wheelImage.width * this.scaleFactor,
            d = this.wheelImage.height * this.scaleFactor,
            e = a - b / 2,
            t = c - d / 2;
        this.ctx.save();
        this.ctx.translate(a, c);
        this.ctx.rotate(this.degToRad(this.rotationAngle));
        this.ctx.translate(-a, -c);
        this.ctx.drawImage(this.wheelImage, e, t, b, d);
        this.ctx.restore();
    }
};
Spinwheel.prototype.drawSegmentImages = function () {
    // console.log("Drawing segment images");
    if (this.ctx) {
        var a = this.centerX * this.scaleFactor,
            c = this.centerY * this.scaleFactor;
        if (this.segments)
            for (var b = 1; b <= this.numSegments; b++) {
                var d = this.segments[b];
                if (d.imgData.height) {
                    var e = d.imgData.width * this.scaleFactor,
                        t = d.imgData.height * this.scaleFactor;
                    var h = null !== d.imageDirection ? d.imageDirection : this.imageDirection;
                    if ("S" == h) {
                        h = a - e / 2;
                        var n = c;
                        var r = d.startAngle + 180 + (d.endAngle - d.startAngle) / 2;
                    } else
                        "E" == h
                            ? ((h = a), (n = c - t / 2), (r = d.startAngle + 270 + (d.endAngle - d.startAngle) / 2))
                            : "W" == h
                            ? ((h = a - e), (n = c - t / 2), (r = d.startAngle + 90 + (d.endAngle - d.startAngle) / 2))
                            : ((h = a - e / 2), (n = c - t), (r = d.startAngle + (d.endAngle - d.startAngle) / 2));
                    this.ctx.save();
                    this.ctx.translate(a, c);
                    this.ctx.rotate(this.degToRad(this.rotationAngle + r));
                    this.ctx.translate(-a, -c);
                    this.ctx.drawImage(d.imgData, h, n, e, t);
                    this.ctx.restore();
                } else console.log("Segment " + b + " imgData is not loaded");
            }
    }
};
Spinwheel.prototype.drawSegments = function () {
    // console.log("Drawing segments");
    if (this.ctx && this.segments){
        for (var a = this.centerX * this.scaleFactor, c = this.centerY * this.scaleFactor, b = this.innerRadius * this.scaleFactor, d = this.outerRadius * this.scaleFactor, e = 1; e <= this.numSegments; e++) {
            var t = this.segments[e];
            var h = null !== t.fillStyle ? t.fillStyle : this.fillStyle;
            this.ctx.fillStyle = h;
            var n = null !== t.lineWidth ? t.lineWidth : this.lineWidth;
            this.ctx.lineWidth = 0;
            var r = null !== t.strokeStyle ? t.strokeStyle : this.strokeStyle;
            if ((this.ctx.strokeStyle = r) || h) {
                this.ctx.beginPath();
                if (this.innerRadius) {
                    var f = Math.cos(this.degToRad(t.startAngle + this.rotationAngle - 90)) * (b - n / 2);
                    n = Math.sin(this.degToRad(t.startAngle + this.rotationAngle - 90)) * (b - n / 2);
                    this.ctx.moveTo(a + f, c + n);
                } else this.ctx.moveTo(a, c);
                this.ctx.arc(a, c, d, this.degToRad(t.startAngle + this.rotationAngle - 90), this.degToRad(t.endAngle + this.rotationAngle - 90), !1);
                this.innerRadius ? this.ctx.arc(a, c, b, this.degToRad(t.endAngle + this.rotationAngle - 90), this.degToRad(t.startAngle + this.rotationAngle - 90), !0) : this.ctx.lineTo(a, c);
                h && this.ctx.fill();
                // r && this.ctx.stroke();
            }
        }
        // console.log("Drawing segments complete", typeof this.drawParts);
        if(typeof this.drawParts() === 'function') {
            this.drawParts();
        }
    }

};
Spinwheel.prototype.drawSegmentText = function () {
    // console.log("Drawing segment text", this.ctx, this);

    if (this.ctx) {
       const svgNS = "http://www.w3.org/2000/svg";
        const svg = document.createElementNS(svgNS, "svg");
        svg.setAttribute("xmlns", svgNS);
        svg.setAttribute("width", this.ctx.canvas.width);
        svg.setAttribute("height", this.ctx.canvas.height);
        svg.setAttribute("style", 'position:absolute; top:2px; left:2px;');
        const anglePerSegment = 2 * Math.PI / this.numSegments;
        const radius = this.ctx.canvas.width/2 - this.innerRadius/2;
        const dd = this.ctx.canvas.width;

        for (var a, c, b, d, e, t, h, n, r, f, l = this.centerX * this.scaleFactor, p = this.centerY * this.scaleFactor, w = this.outerRadius * this.scaleFactor, v = this.innerRadius * this.scaleFactor, y = 1; y <= this.numSegments; y++) {
            var g = this.segments[y];
            if (!g.text) continue;
            const angle = (y) * anglePerSegment;
            // const m = this.degToRad(g.endAngle - (g.endAngle - g.startAngle) / 2 + this.rotationAngle - 90 - 180);
            // const xVal = this.centerX + (radius - parseInt(360/this.numSegments)) * Math.cos(angle);
            // const yVal = this.centerY + (radius - parseInt(360/this.numSegments)) * Math.sin(angle);

            // const angle = (i + 0.5) * anglePerSegment;
            const xVal = this.centerX + (radius - 360/this.numSegments) * Math.cos(angle);
            const yVal = this.centerY + (radius - 360/this.numSegments) * Math.sin(angle);
            const rotation = (angle * 180 / Math.PI);
            // const rotation = (angle * 180 / Math.PI);
            // console.log("Drawing segment text for segment", radius, anglePerSegment, angle, xVal, yVal, (radius - 360/this.numSegments) * Math.cos(angle), (radius - 360/this.numSegments) * Math.sin(angle));

            const text = document.createElementNS(svgNS, "text");
            text.setAttribute("x", xVal);
            text.setAttribute("y", yVal);
            text.setAttribute("text-anchor", "middle");
            text.setAttribute("dominant-baseline", "middle");
            // text.setAttribute("transform", `rotate(${rotation}, ${x}, ${y})`);
            text.setAttribute("style", "font-family: Arial; fill: #000;");

            const tspan1 = document.createElementNS(svgNS, "tspan");
            tspan1.setAttribute("x", xVal);
            tspan1.setAttribute("dy", "-0.5em");
            tspan1.setAttribute("style", "font-size:14px; font-weight: bold;");
            tspan1.textContent = g.text;

            // const tspan2 = document.createElementNS(svgNS, "tspan");
            // tspan2.setAttribute("x", x);
            // tspan2.setAttribute("dy", "1.2em");
            // tspan2.setAttribute("style", "font-size:12px;");
            // tspan2.textContent = labels[i].line2;

            text.appendChild(tspan1);
            // text.appendChild(tspan2);
            svg.appendChild(text);

            this.ctx.save();
            var g = this.segments[y];
            // console.log("Drawing segment text for segment", y, g);
            if (g.text) {
                // const text = g.text.replace(/\\n/g, '');
                // g.text = text;
                // console.log("Drawing segment text for segment", y, "text", text);
                a = null !== g.textFontFamily ? g.textFontFamily : this.textFontFamily;
                c = null !== g.textFontSize ? g.textFontSize : this.textFontSize;
                b = null !== g.textFontWeight ? g.textFontWeight : this.textFontWeight;
                d = null !== g.textOrientation ? g.textOrientation : this.textOrientation;
                // d = 'curved';
                e = null !== g.textAlignment ? g.textAlignment : this.textAlignment;
                // e = 'top';
                t = null !== g.textDirection ? g.textDirection : this.textDirection;
                h = null !== g.textMargin ? g.textMargin : this.textMargin;
                n = null !== g.textFillStyle ? g.textFillStyle : this.textFillStyle;
                r = null !== g.textStrokeStyle ? g.textStrokeStyle : this.textStrokeStyle;
                f = null !== g.textLineWidth ? g.textLineWidth : this.textLineWidth;
                c *= this.scaleFactor;
                h *= this.scaleFactor;
                var m = "";
                null != b && (m += b + " ");
                null != c && (m += c + "px ");
                null != a && (m += a);
                this.ctx.font = m;
                this.ctx.fillStyle = n;
                this.ctx.strokeStyle = r;
                this.ctx.lineWidth = f;
                a = g.text.split("\\n");
                // console.log("Drawing segment text for segment", y, "text", a, "font", m, "fillStyle", n, "strokeStyle", r, "lineWidth", f, d);
                b = -((a.length / 2) * c) + c / 2;
                "curved" != d || ("inner" != e && "outer" != e) || (b = 0);
                for (f = 0; f < a.length; f++) {
                    if ("reversed" == t)
                        if ("horizontal" == d)
                            (this.ctx.textAlign = "inner" == e ? "right" : "outer" == e ? "left" : "center"),
                                (this.ctx.textBaseline = "middle"),
                                (m = this.degToRad(g.endAngle - (g.endAngle - g.startAngle) / 2 + this.rotationAngle - 90 - 180)),
                                this.ctx.save(),
                                this.ctx.translate(l, p),
                                this.ctx.rotate(m),
                                this.ctx.translate(-l, -p),
                                "inner" == e
                                    ? (n && this.ctx.fillText(a[f], l - v - h, p + b), r && this.ctx.strokeText(a[f], l - v - h, p + b))
                                    : "outer" == e
                                    ? (n && this.ctx.fillText(a[f], l - w + h, p + b), r && this.ctx.strokeText(a[f], l - w + h, p + b))
                                    : (n && this.ctx.fillText(a[f], l - v - (w - v) / 2 - h, p + b), r && this.ctx.strokeText(a[f], l - v - (w - v) / 2 - h, p + b)),
                                this.ctx.restore();
                        else if ("vertical" == d) {
                            this.ctx.textAlign = "center";
                            this.ctx.textBaseline = "inner" == e ? "top" : "outer" == e ? "bottom" : "middle";
                            m = g.endAngle - (g.endAngle - g.startAngle) / 2 - 180;
                            m += this.rotationAngle;
                            this.ctx.save();
                            this.ctx.translate(l, p);
                            this.ctx.rotate(this.degToRad(m));
                            this.ctx.translate(-l, -p);
                            var k = 0;
                            "outer" == e ? (k = p + w - h) : "inner" == e && (k = p + v + h);
                            m = c - c / 9;
                            if ("outer" == e)
                                for (var q = a[f].length - 1; 0 <= q; q--) {
                                    var u = a[f].charAt(q);
                                    n && this.ctx.fillText(u, l + b, k);
                                    r && this.ctx.strokeText(u, l + b, k);
                                    k -= m;
                                }
                            else if ("inner" == e) for (q = 0; q < a[f].length; q++) (u = a[f].charAt(q)), n && this.ctx.fillText(u, l + b, k), r && this.ctx.strokeText(u, l + b, k), (k += m);
                            else if ("center" == e)
                                for (k = 0, 1 < a[f].length && (k = (m * (a[f].length - 1)) / 2), k = p + v + (w - v) / 2 + k + h, q = a[f].length - 1; 0 <= q; q--)
                                    (u = a[f].charAt(q)), n && this.ctx.fillText(u, l + b, k), r && this.ctx.strokeText(u, l + b, k), (k -= m);
                            this.ctx.restore();
                        } else {
                            if ("curved" == d)
                                for (
                                    m = 0,
                                        "inner" == e
                                            ? ((m = v + h), (this.ctx.textBaseline = "top"))
                                            : "outer" == e
                                            ? ((m = w - h), (this.ctx.textBaseline = "bottom"), (m -= c * (a.length - 1)))
                                            : "center" == e && ((m = v + h + (w - v) / 2), (this.ctx.textBaseline = "middle")),
                                        k = 0,
                                        1 < a[f].length
                                            ? ((this.ctx.textAlign = "left"), (k = (c / 10) * 4), (k *= 100 / m), (q = g.startAngle + ((g.endAngle - g.startAngle) / 2 - (k * a[f].length) / 2)))
                                            : ((q = g.startAngle + (g.endAngle - g.startAngle) / 2), (this.ctx.textAlign = "center")),
                                        q += this.rotationAngle,
                                        q -= 180,
                                        u = a[f].length;
                                    0 <= u;
                                    u--
                                ) {
                                    this.ctx.save();
                                    var x = a[f].charAt(u);
                                    this.ctx.translate(l, p);
                                    this.ctx.rotate(this.degToRad(q));
                                    this.ctx.translate(-l, -p);
                                    r && this.ctx.strokeText(x, l, p + m + b);
                                    n && this.ctx.fillText(x, l, p + m + b);
                                    q += k;
                                    this.ctx.restore();
                                }
                        }
                    else if ("horizontal" == d)
                        (this.ctx.textAlign = "inner" == e ? "left" : "outer" == e ? "right" : "center"),
                            (this.ctx.textBaseline = "middle"),
                            (m = this.degToRad(g.endAngle - (g.endAngle - g.startAngle) / 2 + this.rotationAngle - 90)),
                            this.ctx.save(),
                            this.ctx.translate(l, p),
                            this.ctx.rotate(m),
                            this.ctx.translate(-l, -p),
                            "inner" == e
                                ? (n && this.ctx.fillText(a[f], l + v + h, p + b), r && this.ctx.strokeText(a[f], l + v + h, p + b))
                                : "outer" == e
                                ? (n && this.ctx.fillText(a[f], l + w - h, p + b), r && this.ctx.strokeText(a[f], l + w - h, p + b))
                                : (n && this.ctx.fillText(a[f], l + v + (w - v) / 2 + h, p + (b < 0 ? b - 3 : b + 3)), r && this.ctx.strokeText(a[f], l + v + (w - v) / 2 + h, p + b)),
                            this.ctx.restore();
                    else if ("vertical" == d) {
                        this.ctx.textAlign = "center";
                        this.ctx.textBaseline = "inner" == e ? "bottom" : "outer" == e ? "top" : "middle";
                        m = g.endAngle - (g.endAngle - g.startAngle) / 2;
                        m += this.rotationAngle;
                        this.ctx.save();
                        this.ctx.translate(l, p);
                        this.ctx.rotate(this.degToRad(m));
                        this.ctx.translate(-l, -p);
                        k = 0;
                        "outer" == e ? (k = p - w + h) : "inner" == e && (k = p - v - h);
                        m = c - c / 9;
                        if ("outer" == e) for (q = 0; q < a[f].length; q++) (u = a[f].charAt(q)), n && this.ctx.fillText(u, l + b, k), r && this.ctx.strokeText(u, l + b, k), (k += m);
                        else if ("inner" == e) for (q = a[f].length - 1; 0 <= q; q--) (u = a[f].charAt(q)), n && this.ctx.fillText(u, l + b, k), r && this.ctx.strokeText(u, l + b, k), (k -= m);
                        else if ("center" == e)
                            for (k = 0, 1 < a[f].length && (k = (m * (a[f].length - 1)) / 2), k = p - v - (w - v) / 2 - k - h, q = 0; q < a[f].length; q++)
                                (u = a[f].charAt(q)), n && this.ctx.fillText(u, l + b, k), r && this.ctx.strokeText(u, l + b, k), (k += m);
                        this.ctx.restore();
                    } else if ("curved" == d)
                        for (
                            m = 0,
                                "inner" == e
                                    ? ((m = v + h), (this.ctx.textBaseline = "bottom"), (m += c * (a.length - 1)))
                                    : "outer" == e
                                    ? ((m = w - h), (this.ctx.textBaseline = "top"))
                                    : "center" == e && ((m = v + h + (w - v) / 2), (this.ctx.textBaseline = "middle")),
                                k = 0,
                                1 < a[f].length
                                    ? ((this.ctx.textAlign = "left"), (k = (c / 10) * 4), (k *= 100 / m), (q = g.startAngle + ((g.endAngle - g.startAngle) / 2 - (k * a[f].length) / 2)))
                                    : ((q = g.startAngle + (g.endAngle - g.startAngle) / 2), (this.ctx.textAlign = "center")),
                                q += this.rotationAngle,
                                u = 0;
                            u < a[f].length;
                            u++
                        )
                            this.ctx.save(),
                                (x = a[f].charAt(u)),
                                this.ctx.translate(l, p),
                                this.ctx.rotate(this.degToRad(q)),
                                this.ctx.translate(-l, -p),
                                r && this.ctx.strokeText(x, l, p - m + b),
                                n && this.ctx.fillText(x, l, p - m + b),
                                (q += k),
                                // console.log("Drawing segment text for segment", y, "text", a[f], "x", l, "y", p, "angle", q),
                                this.ctx.restore();
                    b += c;
                }
            }
            this.ctx.restore();
        }

        // $('.column.main').append(svg);
        // const container = document.querySelector('.spin-view__wheel-container');
        // if (container) {
        //     container.appendChild(svg);
        // }
        // Step 3: Convert SVG to image
        // const svgString = new XMLSerializer().serializeToString(svg);
        // const svgBlob = new Blob([svgString], { type: "image/svg+xml;charset=utf-8" });
        // const url = URL.createObjectURL(svgBlob);

        // const image = new Image();
        // const self = this;
        // image.onload = function () {
        //     self.ctx.save();
        //     self.ctx.drawImage(image, 0, 0);
        //     self.ctx.restore();
        //     URL.revokeObjectURL(url);
        // };
        // image.src = url;
    }
        
};
Spinwheel.prototype.degToRad = function (a) {
    return 0.017453292519943295 * a;
};
Spinwheel.prototype.setCenter = function (a, c) {
    this.centerX = a;
    this.centerY = c;
};
Spinwheel.prototype.addSegment = function (a, c) {
    var b = new Segment(a);
    this.numSegments++;
    if ("undefined" !== typeof c) {
        for (var d = this.numSegments; d > c; d--) this.segments[d] = this.segments[d - 1];
        this.segments[c] = b;
        b = c;
    } else (this.segments[this.numSegments] = b), (b = this.numSegments);
    this.updateSegmentSizes();
    return this.segments[b];
};
Spinwheel.prototype.setCanvasId = function (a) {
    if (a) {
        if (((this.canvasId = a), (this.canvas = document.getElementById(this.canvasId)))) this.ctx = this.canvas.getContext("2d");
    } else this.canvas = this.ctx = this.canvasId = null;
};
Spinwheel.prototype.deleteSegment = function (a) {
    if (1 < this.numSegments) {
        if ("undefined" !== typeof a) for (; a < this.numSegments; a++) this.segments[a] = this.segments[a + 1];
        this.segments[this.numSegments] = void 0;
        this.numSegments--;
        this.updateSegmentSizes();
    }
};
Spinwheel.prototype.windowToCanvas = function (a, c) {
    var b = this.canvas.getBoundingClientRect();
    return { x: Math.floor(a - (this.canvas.width / b.width) * b.left), y: Math.floor(c - (this.canvas.height / b.height) * b.top) };
};
Spinwheel.prototype.getSegmentAt = function (a, c) {
    var b = null,
        d = this.getSegmentNumberAt(a, c);
    null !== d && (b = this.segments[d]);
    return b;
};
Spinwheel.prototype.getSegmentNumberAt = function (a, c) {
    var b = this.windowToCanvas(a, c);
    var d = this.centerX * this.scaleFactor;
    var e = this.centerY * this.scaleFactor;
    var t = this.outerRadius * this.scaleFactor,
        h = this.innerRadius * this.scaleFactor;
    if (b.x > d) {
        var n = b.x - d;
        d = "R";
    } else (n = d - b.x), (d = "L");
    if (b.y > e) {
        var r = b.y - e;
        e = "B";
    } else (r = e - b.y), (e = "T");
    var f = (180 * Math.atan(r / n)) / Math.PI;
    b = 0;
    n = Math.sqrt(r * r + n * n);
    "T" == e && "R" == d ? (b = Math.round(90 - f)) : "B" == e && "R" == d ? (b = Math.round(f + 90)) : "B" == e && "L" == d ? (b = Math.round(90 - f + 180)) : "T" == e && "L" == d && (b = Math.round(f + 270));
    0 != this.rotationAngle && ((d = this.getRotationPosition()), (b -= d), 0 > b && (b = 360 - Math.abs(b)));
    d = null;
    for (e = 1; e <= this.numSegments; e++)
        if (b >= this.segments[e].startAngle && b <= this.segments[e].endAngle && n >= h && n <= t) {
            d = e;
            break;
        }
    return d;
};
Spinwheel.prototype.getIndicatedSegment = function () {
    var a = this.getIndicatedSegmentNumber();
    return this.segments[a];
};
Spinwheel.prototype.getIndicatedSegmentNumber = function () {
    var a = 0,
        c = this.getRotationPosition();
    c = Math.floor(this.pointerAngle - c);
    0 > c && (c = 360 - Math.abs(c));
    for (var b = 1; b < this.segments.length; b++)
        if (c >= this.segments[b].startAngle && c <= this.segments[b].endAngle) {
            a = b;
            break;
        }
    return a;
};
Spinwheel.prototype.getCurrentPinNumber = function () {
    var a = 0;
    if (this.pins) {
        var c = this.getRotationPosition();
        c = Math.floor(this.pointerAngle - c);
        0 > c && (c = 360 - Math.abs(c));
        for (var b = 360 / this.pins.number, d = 0, e = 0; e < this.pins.number; e++) {
            if (c >= d && c <= d + b) {
                a = e;
                break;
            }
            d += b;
        }
        "clockwise" == this.animation.direction && (a++, a > this.pins.number && (a = 0));
    }
    return a;
};
Spinwheel.prototype.getRotationPosition = function () {
    var a = this.rotationAngle;
    0 <= a ? 360 < a && (a -= 360 * Math.floor(a / 360)) : (-360 > a && (a -= 360 * Math.ceil(a / 360)), (a = 360 + a));
    return a;
};
Spinwheel.prototype.startAnimation = function () {
    if (this.animation) {
        this.computeAnimation();
        spinwheelToDrawDuringAnimation = this;
        var a = Array(null);
        a[this.animation.propertyName] = this.animation.propertyValue;
        a.yoyo = this.animation.yoyo;
        a.repeat = this.animation.repeat;
        a.ease = this.animation.easing;
        a.onUpdate = spinwheelAnimationLoop;
        a.onComplete = spinwheelStopAnimation;
        this.tween = TweenMax.to(this, this.animation.duration, a);
    }
};
Spinwheel.prototype.stopAnimation = function (a) {
    spinwheelToDrawDuringAnimation && (spinwheelToDrawDuringAnimation.tween && spinwheelToDrawDuringAnimation.tween.kill(), spinwheelStopAnimation(a));
    spinwheelToDrawDuringAnimation = this;
};
Spinwheel.prototype.pauseAnimation = function () {
    this.tween && this.tween.pause();
};
Spinwheel.prototype.resumeAnimation = function () {
    this.tween && this.tween.play();
};
Spinwheel.prototype.computeAnimation = function () {
    this.animation &&
        ("spinOngoing" == this.animation.type
            ? ((this.animation.propertyName = "rotationAngle"),
              null == this.animation.spins && (this.animation.spins = 5),
              null == this.animation.repeat && (this.animation.repeat = -1),
              null == this.animation.easing && (this.animation.easing = "Linear.easeNone"),
              null == this.animation.yoyo && (this.animation.yoyo = !1),
              (this.animation.propertyValue = 360 * this.animation.spins),
              "anti-clockwise" == this.animation.direction && (this.animation.propertyValue = 0 - this.animation.propertyValue))
            : "spinToStop" == this.animation.type
            ? ((this.animation.propertyName = "rotationAngle"),
              null == this.animation.spins && (this.animation.spins = 5),
              null == this.animation.repeat && (this.animation.repeat = 0),
              null == this.animation.easing && (this.animation.easing = "Power3.easeOut"),
              (this.animation._stopAngle = null == this.animation.stopAngle ? Math.floor(359 * Math.random()) : 360 - this.animation.stopAngle + this.pointerAngle),
              null == this.animation.yoyo && (this.animation.yoyo = !1),
              (this.animation.propertyValue = 360 * this.animation.spins),
              "anti-clockwise" == this.animation.direction
                  ? ((this.animation.propertyValue = 0 - this.animation.propertyValue), (this.animation.propertyValue -= 360 - this.animation._stopAngle))
                  : (this.animation.propertyValue += this.animation._stopAngle))
            : "spinAndBack" == this.animation.type &&
              ((this.animation.propertyName = "rotationAngle"),
              null == this.animation.spins && (this.animation.spins = 5),
              null == this.animation.repeat && (this.animation.repeat = 1),
              null == this.animation.easing && (this.animation.easing = "Power2.easeInOut"),
              null == this.animation.yoyo && (this.animation.yoyo = !0),
              (this.animation._stopAngle = null == this.animation.stopAngle ? 0 : 360 - this.animation.stopAngle),
              (this.animation.propertyValue = 360 * this.animation.spins),
              "anti-clockwise" == this.animation.direction
                  ? ((this.animation.propertyValue = 0 - this.animation.propertyValue), (this.animation.propertyValue -= 360 - this.animation._stopAngle))
                  : (this.animation.propertyValue += this.animation._stopAngle)));
};

Spinwheel.prototype.getStopAtAngle = function (a) {
    var c = 0;
    if (a)
        if ("undefined" !== typeof this.segments[a]) {
            var b = this.segments[a].startAngle;
            a = this.segments[a].endAngle - ((this.segments[a].endAngle - b)/2);
            c = a + 90;
        } else console.log("Segment " + a + " undefined");
    else console.log("Segment number not specified");
    return c;
}

Spinwheel.prototype.getRandomForSegment = function (a) {
    var c = 0;
    if (a)
        if ("undefined" !== typeof this.segments[a]) {
            var b = this.segments[a].startAngle;
            a = this.segments[a].endAngle - b - 2;
            0 < a ? (c = b + 1 + Math.floor(Math.random() * a)) : console.log("Segment size is too small to safely get random angle inside it");
        } else console.log("Segment " + a + " undefined");
    else console.log("Segment number not specified");
    // console.log("Random angle for segment ",a,c, this.segments[a]);
    return c;
};


Spinwheel.prototype.drawParts = function() {
    // console.log("Drawing parts", this);
    if(this.parts) {
        this.ctx.strokeStyle = this.parts.strokeStyle;
        this.ctx.fillStyle = this.parts.fillStyle;
        this.ctx.lineWidth = this.parts.lineWidth;
        this.ctx.beginPath();
        // console.log(
        //     "Drawing inner circle with radius: ", {
        //     centerx,
        //     height: this.centerX,
        //     radius: this.parts.innerRadius,
        //     s: 0,
        //     b: 2 * Math.PI
        //     });
        this.ctx.arc(
            centerx,
            this.centerX,
            this.parts.innerRadius,
            0,
            2 * Math.PI
        );
        this.ctx.stroke();
        this.ctx.fill();
    }
}

function Pin(a) {
    var c = { visible: !0, number: 36, outerRadius: 3, fillStyle: "grey", strokeStyle: "black", lineWidth: 1, margin: 3, responsive: !1 },
        b;
    for (b in c) this[b] = null != a && "undefined" !== typeof a[b] ? a[b] : c[b];
    if (null != a) for (var d in a) "undefined" === typeof this[d] && (this[d] = a[d]);
}
function Animation(a) {
    var c = {
            type: "spinOngoing",
            direction: "clockwise",
            propertyName: null,
            propertyValue: null,
            duration: 10,
            yoyo: !1,
            repeat: null,
            easing: null,
            stopAngle: null,
            spins: null,
            clearTheCanvas: null,
            callbackFinished: null,
            callbackBefore: null,
            callbackAfter: null,
            callbackSound: null,
            soundTrigger: "segment",
        },
        b;
    for (b in c) this[b] = null != a && "undefined" !== typeof a[b] ? a[b] : c[b];
    if (null != a) for (var d in a) "undefined" === typeof this[d] && (this[d] = a[d]);
}
function Segment(a) {
    var c = {
            size: null,
            text: "",
            fillStyle: null,
            strokeStyle: null,
            lineWidth: null,
            textFontFamily: null,
            textFontSize: null,
            textFontWeight: null,
            textOrientation: null,
            textAlignment: null,
            textDirection: null,
            textMargin: null,
            textFillStyle: null,
            textStrokeStyle: null,
            textLineWidth: null,
            image: null,
            imageDirection: null,
            imgData: null,
        },
        b;
    for (b in c) this[b] = null != a && "undefined" !== typeof a[b] ? a[b] : c[b];
    if (null != a) for (var d in a) "undefined" === typeof this[d] && (this[d] = a[d]);
    this.endAngle = this.startAngle = 0;
}
Segment.prototype.changeImage = function (a, c) {
    this.image = a;
    this.imgData = null;
    c && (this.imageDirection = c);
    spinhweelAlreadyDrawn = !1;
    this.imgData = new Image();
    this.imgData.onload = spinwheelLoadedImage;
    this.imgData.src = this.image;
};
function PointerGuide(a) {
    var c = { display: !1, strokeStyle: "red", lineWidth: 3 },
        b;
    for (b in c) this[b] = null != a && "undefined" !== typeof a[b] ? a[b] : c[b];
}
function spinwheelPercentToDegrees(a) {
    var c = 0;
    0 < a && 100 >= a && (c = (a / 100) * 360);
    return c;
}
function spinwheelAnimationLoop() {
    if (spinwheelToDrawDuringAnimation) {
        0 != spinwheelToDrawDuringAnimation.animation.clearTheCanvas && spinwheelToDrawDuringAnimation.ctx.clearRect(0, 0, spinwheelToDrawDuringAnimation.canvas.width, spinwheelToDrawDuringAnimation.canvas.height);
        var a = spinwheelToDrawDuringAnimation.animation.callbackBefore,
            c = spinwheelToDrawDuringAnimation.animation.callbackAfter;
        null != a && ("function" === typeof a ? a() : eval(a));
        spinwheelToDrawDuringAnimation.draw(!1);
        null != c && ("function" === typeof c ? c() : eval(c));
        spinwheelToDrawDuringAnimation.animation.callbackSound && spinwheelTriggerSound();
    }
}
function spinwheelTriggerSound() {
    0 == spinwheelToDrawDuringAnimation.hasOwnProperty("_lastSoundTriggerNumber") && (spinwheelToDrawDuringAnimation._lastSoundTriggerNumber = 0);
    var a = spinwheelToDrawDuringAnimation.animation.callbackSound;
    var c = "pin" == spinwheelToDrawDuringAnimation.animation.soundTrigger ? spinwheelToDrawDuringAnimation.getCurrentPinNumber() : spinwheelToDrawDuringAnimation.getIndicatedSegmentNumber();
    c != spinwheelToDrawDuringAnimation._lastSoundTriggerNumber && ("function" === typeof a ? a() : eval(a), (spinwheelToDrawDuringAnimation._lastSoundTriggerNumber = c));
}
var spinwheelToDrawDuringAnimation = null;
function spinwheelStopAnimation(a) {
    0 != a && ((a = spinwheelToDrawDuringAnimation.animation.callbackFinished), null != a && ("function" === typeof a ? a(spinwheelToDrawDuringAnimation.getIndicatedSegment()) : eval(a)));
}
var spinhweelAlreadyDrawn = !1;
function spinwheelLoadedImage() {
    if (0 == spinhweelAlreadyDrawn) {
        for (var a = 0, c = 1; c <= spinwheelToDrawDuringAnimation.numSegments; c++) null != spinwheelToDrawDuringAnimation.segments[c].imgData && spinwheelToDrawDuringAnimation.segments[c].imgData.height && a++;
        a == spinwheelToDrawDuringAnimation.numSegments && ((spinhweelAlreadyDrawn = !0), spinwheelToDrawDuringAnimation.draw());
    }
}
function spinwheelResize() {
    var a = 40;
    "undefined" !== typeof spinwheelToDrawDuringAnimation._responsiveMargin && (a = spinwheelToDrawDuringAnimation._responsiveMargin);
    var c = window.innerWidth - a,
        b = spinwheelToDrawDuringAnimation._responsiveMinWidth;
    a = spinwheelToDrawDuringAnimation._responsiveMinHeight;
    c < b ? (c = b) : c > spinwheelToDrawDuringAnimation._originalCanvasWidth && (c = spinwheelToDrawDuringAnimation._originalCanvasWidth);
    c /= spinwheelToDrawDuringAnimation._originalCanvasWidth;
    spinwheelToDrawDuringAnimation.canvas.width = spinwheelToDrawDuringAnimation._originalCanvasWidth * c;
    spinwheelToDrawDuringAnimation._responsiveScaleHeight &&
        ((b = spinwheelToDrawDuringAnimation._originalCanvasHeight * c),
        b < a ? (b = a) : b > spinwheelToDrawDuringAnimation._originalCanvasHeight && (b = spinwheelToDrawDuringAnimation._originalCanvasHeight),
        (spinwheelToDrawDuringAnimation.canvas.height = b));
    spinwheelToDrawDuringAnimation.scaleFactor = c;
    spinwheelToDrawDuringAnimation.draw();
}

function playSound() {}
