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
import com.dumontierlab.capac.rink.client.ui.ResourceLink;
import com.dumontierlab.pdb2rdf.ui.client.model.Publication;
import com.google.gwt.core.client.GWT;
import com.google.gwt.user.client.Window;
import com.google.gwt.user.client.rpc.AsyncCallback;
import com.google.gwt.user.client.ui.Composite;
import com.google.gwt.user.client.ui.FlowPanel;
import com.google.gwt.user.client.ui.InlineLabel;

/**
 * @author Alexander De Leon
 */
public class Publications extends Composite {

	private final FlowPanel panel = new FlowPanel();

	public Publications(String pdbId) {
		initWidget(panel);
		getData(pdbId);
	}

	private void getData(final String pdbId) {
		Helper helper = Helper.getInstance();
		helper.getPublications(pdbId, new AsyncCallback<Collection<Publication>>() {
			public void onFailure(Throwable caught) {
				Window.alert("Error: " + caught.getMessage());
				GWT.log("error getting publications", caught);
			}

			public void onSuccess(Collection<Publication> result) {
				for (Publication pub : result) {
					panel.add(new ResourceLink(pub));
					FlowPanel authorsPanel = new FlowPanel();
					authorsPanel.add(new InlineLabel("Authors: "));
					panel.add(authorsPanel);
					getAuthorsData(pdbId, pub.getUri(), authorsPanel);
				}
			}

		});
	}

	private void getAuthorsData(String pdbId, final String uri, final FlowPanel authorsPanel) {
		Helper helper = Helper.getInstance();
		helper.getAuthors(pdbId, uri, new AsyncCallback<Collection<AnnotatedResource>>() {
			public void onFailure(Throwable caught) {
				Window.alert("Error: " + caught.getMessage());
				GWT.log("error getting authors for: " + uri, caught);
			}

			public void onSuccess(Collection<AnnotatedResource> result) {
				for (AnnotatedResource author : result) {
					authorsPanel.add(new ResourceLink(author));
				}

			}
		});
	}
}
