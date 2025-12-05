package agrochamba.com.data

import com.squareup.moshi.*
import com.squareup.moshi.Types
import java.lang.reflect.Type

/**
 * Clase para manejar empresa que puede venir como string o como objeto
 */
sealed class EmpresaData {
    data class StringValue(val value: String) : EmpresaData()
    data class ObjectValue(val id: Int, val nameValue: String, val slug: String) : EmpresaData()
    
    val name: String
        get() = when (this) {
            is StringValue -> value
            is ObjectValue -> nameValue
        }
}

/**
 * Adaptador para EmpresaData
 */
class EmpresaDataAdapter {
    @FromJson
    fun fromJson(reader: JsonReader): EmpresaData? {
        return when (reader.peek()) {
            JsonReader.Token.STRING -> {
                EmpresaData.StringValue(reader.nextString())
            }
            JsonReader.Token.BEGIN_OBJECT -> {
                reader.beginObject()
                var id: Int? = null
                var name: String? = null
                var slug: String? = null
                while (reader.hasNext()) {
                    when (reader.nextName()) {
                        "id" -> id = reader.nextInt()
                        "name" -> name = reader.nextString()
                        "slug" -> slug = reader.nextString()
                        else -> reader.skipValue()
                    }
                }
                reader.endObject()
                if (id != null && name != null && slug != null) {
                    EmpresaData.ObjectValue(id, name, slug)
                } else {
                    null
                }
            }
            JsonReader.Token.NULL -> {
                reader.skipValue()
                null
            }
            else -> {
                reader.skipValue()
                null
            }
        }
    }

    @ToJson
    fun toJson(writer: JsonWriter, value: EmpresaData?) {
        if (value == null) {
            writer.nullValue()
        } else {
            when (value) {
                is EmpresaData.StringValue -> writer.value(value.value)
                is EmpresaData.ObjectValue -> {
                    writer.beginObject()
                    writer.name("id").value(value.id)
                    writer.name("name").value(value.nameValue)
                    writer.name("slug").value(value.slug)
                    writer.endObject()
                }
            }
        }
    }
}

/**
 * Factory para registrar el adaptador de EmpresaData
 */
class EmpresaDataAdapterFactory : JsonAdapter.Factory {
    override fun create(type: Type, annotations: MutableSet<out Annotation>, moshi: Moshi): JsonAdapter<*>? {
        if (Types.getRawType(type) == EmpresaData::class.java) {
            val adapter = EmpresaDataAdapter()
            return object : JsonAdapter<EmpresaData>() {
                override fun fromJson(reader: JsonReader): EmpresaData? = adapter.fromJson(reader)
                override fun toJson(writer: JsonWriter, value: EmpresaData?) = adapter.toJson(writer, value)
            }
        }
        return null
    }
}

