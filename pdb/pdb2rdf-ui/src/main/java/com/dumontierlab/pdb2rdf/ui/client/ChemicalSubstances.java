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

import com.dumontierlab.capac.rink.client.rdf.Resource;
import com.dumontierlab.capac.rink.client.ui.ResourceLink;
import com.dumontierlab.pdb2rdf.ui.client.model.ChemicalSubstance;
import com.google.gwt.core.client.GWT;
import com.google.gwt.user.client.Window;
import com.google.gwt.user.client.rpc.AsyncCallback;
import com.google.gwt.user.client.ui.Composite;
import com.google.gwt.user.client.ui.FlexTable;
import com.google.gwt.user.client.ui.FlowPanel;
import com.google.gwt.user.client.ui.HTML;
import com.google.gwt.user.client.ui.Label;

/**
 * @author Alexander De Leon
 */
public class ChemicalSubstances extends Composite {

	private static final String HEADER_STYLE_NAME = "pdb2rdf-tableHeader";

	private final FlexTable table = new FlexTable();

	public ChemicalSubstances(String pdbId) {
		table.setWidget(0, 0, new Label("Name"));
		table.setWidget(0, 1, new Label("Type"));
		table.setWidget(0, 2, new Label("Amount"));
		table.getRowFormatter().setStyleName(0, HEADER_STYLE_NAME);
		initWidget(table);
		getData(pdbId);
	}

	private void getData(String pdbId) {
		Helper helper = Helper.getInstance();
		helper.getChemicalSubstances(pdbId, new AsyncCallback<Collection<ChemicalSubstance>>() {
			public void onFailure(Throwable caught) {
				Window.alert("Error: " + caught.getMessage());
				GWT.log("Unable to get chemical substances", caught);
			}

			public void onSuccess(Collection<ChemicalSubstance> result) {
				int row = 1;
				for (ChemicalSubstance substance : result) {
					table.setWidget(row, 0, new ResourceLink(substance));
					FlowPanel typesPanel = new FlowPanel();
					for (Resource type : substance.getTypes()) {
						typesPanel.add(new ResourceLink(type));
						typesPanel.add(new HTML("<br />"));
					}
					table.setWidget(row, 1, typesPanel);
					table.setWidget(row, 2, new Label(substance.getAmount()));
					table.setWidget(row, 3, new Label(substance.getFormula()));

					row++;
				}
			}
		});

	}
}
