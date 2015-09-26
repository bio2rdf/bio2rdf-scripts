package chebi.service;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStreamWriter;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Properties;

import uk.ac.ebi.chebi.webapps.chebiWS.client.ChebiWebServiceClient;
import uk.ac.ebi.chebi.webapps.chebiWS.model.ChebiWebServiceFault_Exception;
import uk.ac.ebi.chebi.webapps.chebiWS.model.*;

public class GetChEBIbyNames {

	private static String absPath = System.getProperty("user.dir");

	public static void main(String[] args) {

		GetChEBIbyNames chebi = new GetChEBIbyNames();

		String configFilePath = absPath + "/mappings/PT-UNII-ChEBI-mapping/ChEBIJavaClient/mappings/config.properties";

		try {

			HashMap<String, String> properties = chebi.readConfigs(configFilePath);
			String inputPath = absPath +"/"+ properties.get("input");
			String outputPath = absPath +"/"+ properties.get("output");

			List<String> drugL = readDrugListFile(inputPath);
			Map<String, String> mappingD = getMappingsD(drugL);
			printToFile(mappingD, outputPath);

		} catch (IOException e) {
			e.printStackTrace();
		}

	}

	public static Map<String, String> getMappingsD(List<String> drugL) {
		Map<String, String> mappingD = new HashMap<String, String>();

		for (String drug : drugL) {
			if (!drug.isEmpty()) {
				System.out.println(drug);
				String chebiURI = getChEBIByName(drug);
				if (!chebiURI.isEmpty())
				    mappingD.put(drug.trim(), ("http://purl.obolibrary.org/obo/"+chebiURI.replace(":","_")));
			}
		}

		return mappingD;
	}

	public HashMap<String, String> readConfigs(String propFilePath) throws IOException {

		try {
			HashMap<String, String> result = new HashMap<String, String>();

			File file = new File(propFilePath);
			FileInputStream fileInput = new FileInputStream(file);
			Properties properties = new Properties();
			properties.load(fileInput);
			fileInput.close();

			String input = properties.getProperty("input");
			String output = properties.getProperty("output");

			if (!input.isEmpty() && !output.isEmpty()) {

				result.put("input", input);
				result.put("output", output);

				return result;
			} else {
				System.out.println("Property no found - input: " + input + " | output: " + output);

			}

		} catch (Exception e) {
			System.out.println("Exception: " + e);
		}
		return null;

	}

	public static String getChEBIByName(String drugname) {

		String chebi = "";

		try {

			ChebiWebServiceClient client = new ChebiWebServiceClient();
			LiteEntityList entities = client.getLiteEntity(drugname, SearchCategory.ALL, 50, StarsCategory.ALL);
			List<LiteEntity> resultList = entities.getListElement();

			for (LiteEntity liteEntity : resultList) {
				if (drugname.toLowerCase().equals(liteEntity.getChebiAsciiName().toLowerCase())) {
					chebi = liteEntity.getChebiId();
				}
			}

		} catch (ChebiWebServiceFault_Exception e) {
			System.err.println(e.getMessage());
		}
		return chebi;
	}

	public static List<String> readDrugListFile(String filePath) {

		List<String> drugL = new ArrayList<String>();

		BufferedReader br;
		try {
			br = new BufferedReader(new FileReader(filePath));

			while (true) {

				String line = br.readLine();

				if (line == null) {
					break;
				}

				if (!line.isEmpty())
					drugL.add(line.trim());
			}
			br.close();

		} catch (Exception e) {
			e.printStackTrace();
		}
		return drugL;
	}

	// write string into file of specified format
	public static void printToFile(Map<String, String> mappingD, String outputPath) {
		File file = new File(outputPath);

		try {

			if (!file.exists()) {
				file.createNewFile();
			}

			FileOutputStream fos = new FileOutputStream(file);

			Iterator it = mappingD.entrySet().iterator();

			FileWriter fw = new FileWriter(file);
			BufferedWriter bw = new BufferedWriter(fw);

			while (it.hasNext()) {
				Map.Entry pair = (Map.Entry) it.next();
				bw.write(pair.getKey() + "\t" + pair.getValue() + "\n");
			}
			bw.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

}
