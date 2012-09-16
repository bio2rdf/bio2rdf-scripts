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

import com.dumontierlab.capac.rink.client.rdf.Resource;
import com.dumontierlab.capac.rink.client.ui.ResourceLink;
import com.google.gwt.core.client.EntryPoint;
import com.google.gwt.core.client.GWT;
import com.google.gwt.user.client.Window;
import com.google.gwt.user.client.rpc.AsyncCallback;
import com.google.gwt.user.client.ui.FlowPanel;
import com.google.gwt.user.client.ui.InlineLabel;
import com.google.gwt.user.client.ui.Label;
import com.google.gwt.user.client.ui.RootPanel;
import com.google.gwt.user.client.ui.Widget;

/**
 * @author Alexander De Leon
 */
public class Pdb2RdfUi implements EntryPoint {

	private static final String TITLE_STYLE_CLASS = "pdb2rdf-title";

	private String pdbId;
	private Label titleLabel;

	public void onModuleLoad() {
		pdbId = Window.Location.getParameter("id");
		if (pdbId != null) {
			RootPanel.get().add(createUi());
			getData();
		}
	}

	private void getData() {
		Helper helper = Helper.getInstance();
		helper.getTitle(pdbId, new AsyncCallback<String>() {

			public void onSuccess(String result) {
				titleLabel.setText(result);
			}

			public void onFailure(Throwable caught) {
				// TODO
				Window.alert("Error: " + caught.getMessage());
				GWT.log("error getting title", caught);
			}
		});

	}

	private Widget createUi() {
		FlowPanel panel = new FlowPanel();
		panel.setStyleName("pdb2rdf-content");
		panel.add(new DownloadsControl(pdbId));
		titleLabel = new Label();
		titleLabel.setStyleName(TITLE_STYLE_CLASS);
		panel.add(titleLabel);
		FlowPanel experimentPanel = new FlowPanel();
		experimentPanel.add(new InlineLabel("Experiment: "));
		experimentPanel.add(new ResourceLink(Resource.createUriResource("http://bio2rdf.org/pdb:" + pdbId)));
		panel.add(experimentPanel);

		FlowPanel leftPanel = new FlowPanel();
		leftPanel.setStyleName("pdb2rdf-leftSide");

		leftPanel.add(new HorizonalBar("STRUCTURE DETERMINATION"));
		leftPanel.add(new StructureDetermination(pdbId));

		leftPanel.add(new HorizonalBar("CHEMICAL SUBSTANCES"));
		leftPanel.add(new ChemicalSubstances(pdbId));

		leftPanel.add(new HorizonalBar("REFERENCES"));
		leftPanel.add(new Publications(pdbId));
		panel.add(leftPanel);

		FlowPanel rightPanel = new FlowPanel();
		rightPanel.setStyleName("pdb2rdf-rightSide");
		rightPanel.add(new Visualization(pdbId));
		panel.add(rightPanel);

		return panel;
	}
}
