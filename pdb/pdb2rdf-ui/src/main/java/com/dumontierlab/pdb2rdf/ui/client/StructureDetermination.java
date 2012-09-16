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

import java.util.Collection;

import com.dumontierlab.capac.rink.client.rdf.AnnotatedResource;
import com.dumontierlab.capac.rink.client.rdf.Resource;
import com.dumontierlab.capac.rink.client.ui.ResourceLink;
import com.dumontierlab.pdb2rdf.ui.client.model.Model;
import com.google.gwt.core.client.GWT;
import com.google.gwt.user.client.Window;
import com.google.gwt.user.client.rpc.AsyncCallback;
import com.google.gwt.user.client.ui.Composite;
import com.google.gwt.user.client.ui.FlexTable;
import com.google.gwt.user.client.ui.FlowPanel;
import com.google.gwt.user.client.ui.HTML;
import com.google.gwt.user.client.ui.InlineLabel;
import com.google.gwt.user.client.ui.Label;

/**
 * @author Alexander De Leon
 */
public class StructureDetermination extends Composite {

	private static final String HEADER_STYLE_NAME = "pdb2rdf-tableHeader";

	private final FlowPanel resourcePanel = new FlowPanel();
	private final FlowPanel typesPanel = new FlowPanel();
	private final FlexTable table = new FlexTable();

	public StructureDetermination(String pdbId) {
		FlowPanel panel = new FlowPanel();
		resourcePanel.add(new InlineLabel("Structure Determination: "));
		panel.add(resourcePanel);
		typesPanel.add(new Label("Type: "));
		panel.add(typesPanel);

		table.setWidget(0, 0, new Label("Model"));
		table.setWidget(0, 1, new Label("Nucleic Acid Structure Features"));
		table.getRowFormatter().setStyleName(0, HEADER_STYLE_NAME);
		panel.add(table);

		initWidget(panel);
		getData(pdbId);
	}

	private void getData(String pdbId) {
		Helper helper = Helper.getInstance();
		helper.getStructureDetermination(pdbId, new AsyncCallback<AnnotatedResource>() {
			public void onFailure(Throwable caught) {
				Window.alert("Error: " + caught.getMessage());
				GWT.log("Unable to get structure determination data", caught);
			}

			public void onSuccess(AnnotatedResource result) {
				if (result != null) {
					resourcePanel.add(new ResourceLink(result));
					for (Resource type : result.getTypes()) {
						typesPanel.add(new ResourceLink(type));
						typesPanel.add(new HTML("<br />"));
					}
				}
			}
		});
		helper.getModels(pdbId, new AsyncCallback<Collection<Model>>() {
			public void onFailure(Throwable caught) {
				Window.alert("Error: " + caught.getMessage());
				GWT.log("Unable to get structure determination data", caught);
			}

			public void onSuccess(Collection<Model> result) {
				boolean hasFeatures = false;
				int row = 1;
				for (Model model : result) {
					table.setWidget(row, 0, new ResourceLink(model));
					if (model.getFeatures() != null) {
						table.setWidget(row, 1, new ResourceLink(model.getFeatures()));
						hasFeatures = true;
					}

					row++;
				}
				if (!hasFeatures) {
					table.getColumnFormatter().setStyleName(1, "hide");
				}
			}
		});

	}

}
