/**
 * Copyright (c) 2009 Dumontierlab
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package com.dumontierlab.pdb2rdf.ui.client;

import com.google.gwt.core.client.GWT;
import com.google.gwt.dom.client.Element;
import com.google.gwt.user.client.DOM;
import com.google.gwt.user.client.Window;
import com.google.gwt.user.client.rpc.AsyncCallback;
import com.google.gwt.user.client.ui.Widget;

/**
 * @author Alexander De Leon
 */
public class Visualization extends Widget {

	private static final String STYLE_NAME = "pdb2rdf-visulization";

	private boolean loaded;
	private final String pdbId;

	public Visualization(String pdbId) {
		this.pdbId = pdbId;
		Element div = DOM.createDiv();
		if (GWT.isScript()) {
			createApplet(div);
		}
		setElement(div);
		setStyleName(STYLE_NAME);
	}

	@Override
	protected void onLoad() {
		super.onLoad();
		if (!loaded && GWT.isScript()) {
			Helper helper = Helper.getInstance();
			helper.getRdf(pdbId, new AsyncCallback<String>() {

				public void onSuccess(String result) {
					load(result);
					loaded = true;
				}

				public void onFailure(Throwable caught) {
					Window.alert(caught.getMessage());
					GWT.log("Unable to get RDF for pdb " + pdbId, caught);
				}
			});
		}
	}

	public static native void createApplet(Element element)
	/*-{
	    var applet = $wnd.jmolApplet('100%');
	    element.innerHTML = applet;
	}-*/;

	public static native void load(String model)
	/*-{
	    $wnd.jmolLoadInline(model);
	}-*/;
}
