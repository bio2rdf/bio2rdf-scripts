import java.io.BufferedInputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;

import java.util.Enumeration;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.Set;
import java.util.Vector;
import java.util.zip.ZipEntry;
import java.util.zip.ZipFile;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;


import org.w3c.dom.Attr;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;


public class ParseEPCfromSML2CSV {
	

	public static void DOMParseSPL(Vector<String> v, Map map)
	{
        DocumentBuilderFactory dbf =DocumentBuilderFactory.newInstance();
        
        DocumentBuilder db =null;
        Document document =null;
        
        try
        {

            db = dbf.newDocumentBuilder();
            
        } catch (ParserConfigurationException e)
        {
            e.printStackTrace();
        }
        
        try
        {
	    document = db.parse(new File("SPL/temp.xml"));////
        } catch (Exception e)
        {
            e.printStackTrace();
        } 
        NodeList nodelist = document.getElementsByTagName("document");
        Element element1 = (Element)nodelist.item(0);
    //    String name1 = element1.getElementsByTagName("component").item(0).getFirstChild().getNodeValue();	        
     //   System.out.println("title: " + name1);
       
        int Is_ind = 0;
        String content = new String();
        Node com = element1.getElementsByTagName("component").item(0);
        if(com.getNodeName().equals("component"))
        {
        	NodeList com_list = com.getChildNodes();
        	for(int i = 0; i < com_list.getLength(); ++i)
        	{
        		if(Is_ind != 0)
        			break;
        		Node structuredBody = com_list.item(i);
        		String name_1 = structuredBody.getNodeName();
        		if(name_1.equals("structuredBody"))
        		{
        			NodeList structuredBody_list = structuredBody.getChildNodes();
        			for(int j = 0; j < structuredBody_list.getLength(); ++j)
        			{
                		if(Is_ind != 0)
                			break;
        				Node com_2 = structuredBody_list.item(j);
        				String name_2 = com_2.getNodeName();
        				if(name_2.equals("component"))
        				{
        					NodeList com_list_2 = com_2.getChildNodes();
        					for(int k = 0; k < com_list_2.getLength(); ++k)
        					{
        		        		if(Is_ind != 0)
        		        			break;
        						Node section = com_list_2.item(k);
        						String name_3 = section.getNodeName();
        						if(name_3.equals("section"))
        						{
        							NodeList section_list = section.getChildNodes();
        							for(int l = 0; l < section_list.getLength(); ++l)
        							{
        								Node subject = section_list.item(l);
        								String name_4 = subject.getNodeName();
        								if(name_4.equals("subject"))
        								{
        									NodeList subject_list = subject.getChildNodes();
        									for(int m = 0; m < subject_list.getLength(); ++m)
        									{
        										Node identifiedsubstance = subject_list.item(m);
        										String name_5 = identifiedsubstance.getNodeName();
        										if(name_5.equals("identifiedSubstance"))
        										{
        	        								NodeList identifiedsubstance_list = identifiedsubstance.getChildNodes();
        	        								for(int n = 0; n < identifiedsubstance_list.getLength(); ++n)
        	        								{
        	        									Node identifiedsubstance_1 = identifiedsubstance_list.item(n);
        	        									String name_6 = identifiedsubstance_1.getNodeName();
        	        									
        	        									if(name_6.equals("identifiedSubstance"))
        	        									{
        	        										NodeList identifiedsubstance_1_list = identifiedsubstance_1.getChildNodes();
        	        										for(int o = 0; o < identifiedsubstance_1_list.getLength(); ++o)
        	        										{
        	        											Node asSpecializedKind = identifiedsubstance_1_list.item(o);
        	        											String name_7 = asSpecializedKind.getNodeName();
	            	        									
	            	        									
	            	        									if(name_7.equals("code"))
	            	        									{              	            	        											                	            	        									
    	            	        									NamedNodeMap NamedNodeMap_NUII = asSpecializedKind.getAttributes();
    	            	        									for(int s = 0; s < NamedNodeMap_NUII.getLength(); ++s)
    	            	        									{
			            	        									Attr attr = (Attr) NamedNodeMap_NUII.item(s);
			            	        									
			            	        									if(attr.getName().equals("code"))
			            	        									{
			            	        										boolean wasSpecified = attr != null && attr.getSpecified();
			            	        								    
		    	            	        								    if(wasSpecified == true)
		    	            	        								    {
		    	            	        								    	String NUII = NamedNodeMap_NUII.getNamedItem("code").getFirstChild().getNodeValue().toUpperCase();
		    	            		        									System.out.println(NUII);
		    	            		        									v.add(NUII);
		    	            	        								    }
			            	        									}
    	            	        									}
	            	        									}

        	        											if(name_7.equals("asSpecializedKind"))
        	            										{
        	            	        								NodeList asSpecializedKind_list = asSpecializedKind.getChildNodes();
        	            	        								for(int p = 0; p < asSpecializedKind_list.getLength(); ++p)
        	            	        								{
        	            	        									Node generalizedMaterialKind = asSpecializedKind_list.item(p);
        	            	        									String name_8 = generalizedMaterialKind.getNodeName();
        	            	        									
                	        											if(name_8.equals("generalizedMaterialKind"))
                	            										{
                	            	        								NodeList generalizedMaterialKind_list = generalizedMaterialKind.getChildNodes();
                	            	        								for(int r = 0; r < generalizedMaterialKind_list.getLength(); ++r)
                	            	        								{
                	            	        									Node nui = generalizedMaterialKind_list.item(r);
                	            	        									String name_9 = nui.getNodeName();
                	            	        									
                	            	        									String NDFRT_ID = new String();
                	            	        									String NDFRT_Name = new String();
                	            	        									if(name_9.equals("code"))
                	            	        									{              	            	        											                	            	        									
	                	            	        									NamedNodeMap NamedNodeMap_5 = nui.getAttributes();
	                	            	        									for(int s = 0; s < NamedNodeMap_5.getLength(); ++s)
	                	            	        									{
		                	            	        									Attr attr = (Attr) NamedNodeMap_5.item(s);
		                	            	        									
		                	            	        									if(attr.getName().equals("code"))
		                	            	        									{
		                	            	        										boolean wasSpecified = attr != null && attr.getSpecified();
		                	            	        								    
			                	            	        								    if(wasSpecified == true)
			                	            	        								    {
			                	            		        									NDFRT_ID = NamedNodeMap_5.getNamedItem("code").getFirstChild().getNodeValue().toUpperCase();
			                	            		        									System.out.println(NDFRT_ID);
			                	            	        								    }
		                	            	        									}
		                	            	        									else if(attr.getName().equals("displayName"))
		                	            	        									{
		                	            	        										boolean wasSpecified = attr != null && attr.getSpecified();
		                	            	        								    
			                	            	        								    if(wasSpecified == true)
			                	            	        								    {
			                	            		        									NDFRT_Name = NamedNodeMap_5.getNamedItem("displayName").getFirstChild().getNodeValue().toUpperCase();
			                	            		        									System.out.println(NDFRT_Name);
			                	            	        								    }
		                	            	        									}
	                	            	        									}
	                	            	        									map.put(NDFRT_ID, NDFRT_Name);
                	            	        									}              	            	        									
                	            	        								}
                	            										}
        	            	        								}
        	            										}
        	        										}
        	        									}
        	        								}
        										}
        									}
        								}
        							}
		        				}
        					}
        				}
        			}
        		}
        	}
        }
	}

	public static void unZipFile() throws IOException
	{
	String path = "SPL/pharmacologic_class_indexing_spl_files_most_recent";
        File dir = new File(path);
        File file[] = dir.listFiles();
	FileWriter fw = new FileWriter("SPL/epc_spl.txt");

        for (int i = 0; i < file.length; i++) 
        {
        	String name = file[i].getAbsolutePath();
		String temp = name.split("pharmacologic_class_indexing_spl_files_most_recent")[1];
        	String temp_1 = temp.substring(1, temp.length());
        	String setid = temp_1.substring(temp_1.indexOf("_") + 1, temp_1.lastIndexOf(".zip"));
			ZipFile zfile=new ZipFile(name);  
			Enumeration zList=zfile.entries();  
			ZipEntry ze=null;  
			byte[] buf=new byte[1024];  
			while(zList.hasMoreElements())
			{  
			    ze=(ZipEntry)zList.nextElement();         
			    if(ze.isDirectory())
			    {  
			        File f=new File(path+ze.getName());  
			        f.mkdir();  
			        continue;  
			    }  
			    String filename = "SPL/temp.xml";
			    if(ze.getName().contains("xml"))
			    {
			    	InputStream is=new BufferedInputStream(zfile.getInputStream(ze));
			    	OutputStream out = new FileOutputStream(new File(filename));
			    	 
			    	int read = 0;
			    	byte[] bytes = new byte[1024];
			     
			    	while ((read = is.read(bytes)) != -1) {
			    		out.write(bytes, 0, read);
			    	}
			     
			    	is.close();
			    	out.flush();
			    	out.close();
	            }
	        }  
	        zfile.close();  
			
			Map m= new HashMap();

			Vector<String> v = new Vector();
	        DOMParseSPL(v, m);
	        
	        //String str1 = new String();
        	
		if(m.size() != 0)
		    {
	        	Set set = m.entrySet(); 
	        	Iterator k = set.iterator(); 
	        	int n = 0;        	
	        	
	        	while(k.hasNext()) 
			    { 
	        		++n;
				Map.Entry me = (Map.Entry)k.next(); 
	            	
				String nui = (String) me.getKey();
				String ndfrt_name = (String) me.getValue();
				fw.write(setid + "\t" + v.get(0) + "\t" + nui + "\t" + ndfrt_name + "\n");
	            	
			    }
		    }
						

	        // String str1 = new String();
        	
		// 	if(m.size() != 0)
		// 	{
	        // 	Set set = m.entrySet(); 
	        // 	Iterator k = set.iterator(); 
	        // 	int n = 0;        	
	        	
	        // 	while(k.hasNext()) 
	        // 	{ 
	        // 		++n;
	        //     	Map.Entry me = (Map.Entry)k.next(); 
	            	
	        //     	String nui = (String) me.getKey();
	        //     	String ndfrt_name = (String) me.getValue();
	        //     	str1 += nui + "|" + ndfrt_name;
	        //     	if(n < m.size())
	        //     		str1 += "$";
	            	
	        // 	}
		// 	}
						
		// 	fw.write(setid + "$" + v.get(0) + "$" + str1 + "\r\n");
			
	        //System.out.println(i + ": " + setid + "$" + v.get(0) + "$" + str1);
        }
        fw.close();
	}
	

	public static void main(String[] args) throws IOException {
		
		unZipFile();
		
	}

}
